#!/bin/bash

########################################
# Exit as soon as any line in the bash script fails.
set -o errexit

# pipefail: the return value of a pipeline is the status of the last command to exit with a non-zero status, or zero if no command exited with a non-zero status
set -o pipefail

WP_TESTS_DIR="/tmp/wordpress-tests-lib"

recreate_db() {
	local DB_NAME=$1
	local DB_USER=$2
	local DB_PASS=$3

	mysqladmin drop $DB_NAME -f --user="$DB_USER" --password="$DB_PASS"$EXTRA
	create_db $DB_NAME $DB_USER $DB_PASS
	echo "Recreated the database ($DB_NAME)."
}

create_db() {
	local DB_NAME=$1
	local DB_USER=$2
	local DB_PASS=$3

	mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA
}

install_db() {
	local DB_NAME=${WP_TEST_DATABASE_NAME}
	local DB_USER=${WP_TEST_DATABASE_USER}
	local DB_PASS=${WP_TEST_DATABASE_PASSWORD}
	local DB_HOST=${WP_TEST_DATABASE_HOST}

	local EXTRA=" --host=$DB_HOST --port=$DB_PORT --protocol=tcp"

	# create database
	if [ $(mysql --user="$DB_USER" --password="$DB_PASS"$EXTRA --execute='show databases;' | grep ^$DB_NAME$) ]
	then
		recreate_db $DB_NAME $DB_USER $DB_PASS
	else
		create_db $DB_NAME $DB_USER $DB_PASS
	fi

    sed -i "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR"/wp-tests-config.php
    sed -i "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR"/wp-tests-config.php
    sed -i "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR"/wp-tests-config.php
    sed -i "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR"/wp-tests-config.php
}

install_db

/app/woocommerce/vendor/phpunit/phpunit/phpunit -c /app/woocommerce/phpunit.xml.dist  -c phpunit.xml.dist --coverage-text
