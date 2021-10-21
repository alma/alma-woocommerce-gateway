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
if [ ! -e /var/www/html/wp-content/plugins/woocommerce/woocommerce.php ] ; then
    echo "Installing & activating woocommerce plugin ... " >&2
    /usr/local/bin/wp --path=/var/www/html --allow-root plugin install https://downloads.wordpress.org/plugin/woocommerce.5.3.0.zip
    /usr/local/bin/wp --path=/var/www/html --allow-root plugin activate woocommerce
    [ ! -d /var/www/html/wp-content/uploads/ ] && mkdir -p /var/www/html/wp-content/uploads && chown "$user:$group" /var/www/html/wp-content/uploads
    cp /var/www/html/wp-content/plugins/woocommerce/assets/images/placeholder.png /var/www/html/wp-content/uploads/woocommerce-placeholder.png
    chown "$user:$group" /var/www/html/wp-content/uploads/woocommerce-placeholder.png || true
fi
if [ -d /var/www/html/wp-content/plugins/alma-woocommerce-gateway ] ; then
    echo >&2 "Activating alma-woocommerce-gateway plugin ..."
    /usr/local/bin/wp --path=/var/www/html --allow-root plugin activate alma-woocommerce-gateway
fi
if [ ! -e /var/www/html/wp-content/languages/fr_FR.mo ] ; then
    echo >&2 "Installing & switching to fr_FR language ..."
    /usr/local/bin/wp --path=/var/www/html --allow-root language core install fr_FR
    /usr/local/bin/wp --path=/var/www/html --allow-root site switch-language fr_FR
fi
chown -R "$user:$group" /var/www/html/wp-content || true

exec "$@"
