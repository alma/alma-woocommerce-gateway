#!/usr/bin/env bash
set -Eeuo pipefail

if [[ "$1" == apache2* ]] || [ "$1" = 'php-fpm' ]; then
	uid="$(id -u)"
	gid="$(id -g)"
	if [ "$uid" = '0' ]; then
		case "$1" in
			apache2*)
				user="${APACHE_RUN_USER:-www-data}"
				group="${APACHE_RUN_GROUP:-www-data}"

				# strip off any '#' symbol ('#1000' is valid syntax for Apache)
				pound='#'
				user="${user#$pound}"
				group="${group#$pound}"
				;;
			*) # php-fpm
				user='www-data'
				group='www-data'
				;;
		esac
	else
		user="$uid"
		group="$gid"
	fi

	if [ ! -e index.php ] && [ ! -e wp-includes/version.php ]; then
		# if the directory exists and WordPress doesn't appear to be installed AND the permissions of it are root:root, let's chown it (likely a Docker-created directory)
		if [ "$uid" = '0' ] && [ "$(stat -c '%u:%g' .)" = '0:0' ]; then
			chown "$user:$group" .
		fi

		echo >&2 "WordPress not found in $PWD - copying now..."
		if [ -n "$(find -mindepth 1 -maxdepth 1 -not -name wp-content)" ]; then
			echo >&2 "WARNING: $PWD is not empty! (copying anyhow)"
		fi
		sourceTarArgs=(
			--create
			--file -
			--directory /usr/src/wordpress
			--owner "$user" --group "$group"
		)
		targetTarArgs=(
			--extract
			--file -
		)
		if [ "$uid" != '0' ]; then
			# avoid "tar: .: Cannot utime: Operation not permitted" and "tar: .: Cannot change mode to rwxr-xr-x: Operation not permitted"
			targetTarArgs+=( --no-overwrite-dir )
		fi
		# loop over "pluggable" content in the source, and if it already exists in the destination, skip it
		# https://github.com/docker-library/wordpress/issues/506 ("wp-content" persisted, "akismet" updated, WordPress container restarted/recreated, "akismet" downgraded)
		for contentPath in \
			/usr/src/wordpress/.htaccess \
			/usr/src/wordpress/wp-content/*/*/ \
		; do
			contentPath="${contentPath%/}"
			[ -e "$contentPath" ] || continue
			contentPath="${contentPath#/usr/src/wordpress/}" # "wp-content/plugins/akismet", etc.
			if [ -e "$PWD/$contentPath" ]; then
				echo >&2 "WARNING: '$PWD/$contentPath' exists! (not copying the WordPress version)"
				sourceTarArgs+=( --exclude "./$contentPath" )
			fi
		done
		tar "${sourceTarArgs[@]}" . | tar "${targetTarArgs[@]}"
		echo >&2 "Complete! WordPress has been successfully copied to $PWD"
	fi

	wpEnvs=( "${!WORDPRESS_@}" )
	if [ ! -s wp-config.php ] && [ "${#wpEnvs[@]}" -gt 0 ]; then
		for wpConfigDocker in \
			wp-config-docker.php \
			/usr/src/wordpress/wp-config-docker.php \
		; do
			if [ -s "$wpConfigDocker" ]; then
				echo >&2 "No 'wp-config.php' found in $PWD, but 'WORDPRESS_...' variables supplied; copying '$wpConfigDocker' (${wpEnvs[*]})"
				# using "awk" to replace all instances of "put your unique phrase here" with a properly unique string (for AUTH_KEY and friends to have safe defaults if they aren't specified with environment variables)
				awk '
					/put your unique phrase here/ {
						cmd = "head -c1m /dev/urandom | sha1sum | cut -d\\  -f1"
						cmd | getline str
						close(cmd)
						gsub("put your unique phrase here", str)
					}
					{ print }
				' "$wpConfigDocker" > wp-config.php
				if [ "$uid" = '0' ]; then
					# attempt to ensure that wp-config.php is owned by the run user
					# could be on a filesystem that doesn't allow chown (like some NFS setups)
					chown "$user:$group" wp-config.php || true
				fi
				break
			fi
		done
	fi
fi

[[ -e /usr/local/bin/env.local ]] && source /usr/local/bin/env.local

if [ ! -x /usr/local/bin/wp ] ; then
    echo >&2 "wp-cli not found or note executable !!!"
    exit 1
fi
if [ ! -x /usr/bin/composer ] ; then
    echo >&2 "composer not found or note executable !!!"
    exit 1
fi
if [ ! -d /var/www/html/wp-content/plugins/alma-woocommerce-gateway ] ; then
    echo >&2 "alma-woocommerce-gateway plugin directory not found !!!"
    exit 1
fi
if [ ! -d /var/www/html/wp-content/plugins/alma-woocommerce-gateway/vendor/alma ] ; then
    echo -n "Running composer composer install in alma-woocommerce-gateway plugin directory ... " >&2
    cd /var/www/html/wp-content/plugins/alma-woocommerce-gateway/
    /usr/bin/composer update >&2
    /usr/bin/composer install --no-dev >&2
    chown -R "$user:$group" /var/www/html/wp-content/plugins/alma-woocommerce-gateway/vendor/ || true
    cd -
fi
# {{{ function wp_cli
#
wp_cli() {
    /usr/local/bin/wp --path=/var/www/html --allow-root $@
}
export -f wp_cli
# }}}
# {{{ function wp_is_installed
#
wp_is_installed() {
    wp_cli core is-installed
}
export -f wp_is_installed
# }}}
# {{{ function wp_has_products
#
wp_has_products() {
    local count=`wp_cli post list --post_type=product | grep -v ID | wc -l`
    [[ $count -gt 0 ]]
}
export -f wp_has_products
# }}}
# {{{ function wc_is_configured
#
wc_is_configured() {
    local modal_dismissed=`wp_cli option get woocommerce_task_list_welcome_modal_dismissed 2>/dev/null`
    [[ "x$modal_dismissed" == "xyes" ]]
}
export -f wc_is_configured
# }}}

if [[ ${WP_DB_CLEAN:-0} -eq 1 ]] && ( wp_is_installed ) ; then
    echo "Cleaning WP databases ..." >&2
    wp_cli db clean --yes
fi
if ( ! wp_is_installed ) ; then
    echo >&2 "Installing WordPress Core ... "
    # don't use wp_cli here because of possible spaces into variables
    /usr/local/bin/wp --path=/var/www/html --allow-root core install \
      --url="$WP_URL" \
      --admin_user="$WP_ADMIN_USER" \
      --admin_password="$WP_ADMIN_PASSWORD" \
      --admin_email="$WP_ADMIN_EMAIL" \
      --title="$WP_TITLE" \
      --skip-email
fi
if ( ! wp_cli plugin is-installed woocommerce ) ; then
    echo >&2 "Installing woocommerce plugin ... "
    [[ -d /var/www/html/wp-content/plugins/woocommerce ]] && rm -rf /var/www/html/wp-content/plugins/woocommerce
    wp_cli plugin install https://downloads.wordpress.org/plugin/woocommerce.5.3.0.zip
    [ ! -d /var/www/html/wp-content/uploads/ ] && mkdir -p /var/www/html/wp-content/uploads
    cp /var/www/html/wp-content/plugins/woocommerce/assets/images/placeholder.png /var/www/html/wp-content/uploads/woocommerce-placeholder.png
fi
if ( ! wp_cli plugin is-active woocommerce ) ; then
    echo >&2 "Activating woocommerce plugin ... "
    wp_cli plugin activate woocommerce
fi
if ( ! wp_cli plugin is-installed wordpress-importer ) ; then
    echo >&2 "Activating wordpress-importer plugin ... "
    wp_cli plugin install wordpress-importer --activate
fi
if ( ! wp_cli plugin is-active wordpress-importer ) ; then
    echo >&2 "Activating wordpress-importer plugin ... "
    wp_cli plugin activate wordpress-importer
fi
if ( ! wp_has_products ) ; then
    echo >&2 "Installing sample products ..."
    wp_cli import ./wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip --quiet > /dev/null 2>&1
fi
if ( ! wp_cli theme is-installed storefront ) ; then
    wp_cli theme install storefront --activate
fi
if ( ! wp_cli theme is-active storefront ) ; then
    wp_cli theme activate storefront
fi

if ( ! wc_is_configured ) ; then
    [[ -x /usr/local/bin/configure-wc.sh ]] \
        && echo >&2 "Configuring WooCommerce ..." \
        && /usr/local/bin/configure-wc.sh
fi
if ( ! wp_cli plugin is-installed woocommerce-multilingual ) ; then
    echo >&2 "Installing woocommerce-multilingual ..."
    wp_cli plugin install woocommerce-multilingual --activate
fi
if ( ! wp_cli plugin is-active woocommerce-multilingual ) ; then
    echo >&2 "Activating woocommerce-multilingual ..."
    wp_cli plugin activate woocommerce-multilingual
fi
if [[ -d /usr/local/plugins ]] ; then
    for plugin_path in `find /usr/local/plugins/ -name "*zip" ` ; do
        plugin_name=`basename $plugin_path | sed 's/\.[0-9]\{1,\}\.[0-9]\{1,\}\.[0-9]\{1,\}\.zip$//'`
        if ( ! wp_cli plugin is-installed $plugin_name ) ; then
            echo >&2 "Installing $plugin_name ..."
            wp_cli plugin install $plugin_path --activate
        fi
        if ( ! wp_cli plugin is-active $plugin_name ) ; then
            echo >&2 "Activating $plugin_name ..."
            wp_cli plugin activate $plugin_name
        fi
    done
fi
if ( ! wp_cli plugin is-active alma-woocommerce-gateway ) ; then
    echo >&2 "Activating alma-woocommerce-gateway plugin ..."
    wp_cli plugin activate alma-woocommerce-gateway
fi
# {{{ supported wp_langs
wp_langs="
fr_FR
it_IT
de_DE
fr_BE
nl_NL
nl_BE
es_ES
"
# }}}
for wp_lang in $wp_langs ; do
    if ( ! wp_cli language core is-installed $wp_lang ) ; then
        echo >&2 "Installing $wp_lang languages ..."
        wp_cli language core install $wp_lang
    fi
done
if ( ! wp_cli language core is-installed fr_FR ) ; then
    echo >&2 "Switching to fr_FR language ..."
    wp_cli site switch-language fr_FR
fi
chown -R "$user:$group" /var/www/html/wp-content || true

exec "$@"
