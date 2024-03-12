#!/usr/bin/env bash
set -ex

WP_VERSION=$1
WC_VERSION=$2

TMPDIR="/tmp"
WP_TESTS_DIR="/tmp/wordpress-tests-lib"
WP_CORE_DIR="/tmp/wordpress"

if [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0] ]]; then
	# version x.x.0 means the first release of the major version, so strip off the .0 and download version x.x
	WP_VERSION=${WP_VERSION%??}
fi

WP_TESTS_TAG="tags/$WP_VERSION"

download() {
    curl -s "$1" > "$2";
}

install_wp() {
	if [ -d $WP_CORE_DIR ]; then
		return;
	fi

	mkdir -p $WP_CORE_DIR

	local ARCHIVE_NAME="wordpress-$WP_VERSION"

	download https://wordpress.org/${ARCHIVE_NAME}.tar.gz  $TMPDIR/wordpress.tar.gz
	tar --strip-components=1 -zxmf $TMPDIR/wordpress.tar.gz -C $WP_CORE_DIR

	download https://raw.github.com/markoheijnen/wp-mysqli/master/db.php $WP_CORE_DIR/wp-content/db.php
}

install_test_suite() {
	local DB_NAME=${WP_TEST_DATABASE_NAME}
	local DB_USER=${WP_TEST_DATABASE_USER}
	local DB_PASS=${WP_TEST_DATABASE_PASSWORD}
	local DB_HOST=${WP_TEST_DATABASE_HOST}

	# set up testing suite if it doesn't yet exist
	if [ ! -d $WP_TESTS_DIR ]; then
		# set up testing suite
		mkdir -p $WP_TESTS_DIR
		rm -rf $WP_TESTS_DIR/{includes,data}
		svn export --quiet --ignore-externals https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/ $WP_TESTS_DIR/includes
		svn export --quiet --ignore-externals https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/ $WP_TESTS_DIR/data
	fi

	if [ ! -f wp-tests-config.php ]; then
		download https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php "$WP_TESTS_DIR"/wp-tests-config.php
		# remove all forward slashes in the end
		WP_CORE_DIR=$(echo $WP_CORE_DIR | sed "s:/\+$::")
		sed -i "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR"/wp-tests-config.php
		sed -i "s:__DIR__ . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR"/wp-tests-config.php
	fi

	download https://downloads.wordpress.org/plugin/woocommerce."${WC_VERSION}".zip $TMPDIR/woocommerce.zip
	unzip $TMPDIR/woocommerce.zip  -d $WP_TESTS_DIR

}



install_wp
install_test_suite
