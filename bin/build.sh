#!/usr/bin/env bash
#TODO: svn checkout, svn commit (if needed) after svn sync
QUIET=1
SYNC_SVN=0
# Define the path to Composer and PHP
PHP_PATH=$(which php 2>/dev/null)
COMPOSER_PATH=$(which composer 2>/dev/null)
if [ ! -x "$PHP_PATH" ]; then
    echo "Can't find or execute PHP on: $PHP_PATH"
    exit 1
fi

# Get PHP version
PHP_VERSION=$($PHP_PATH -v | head -n 1 | awk '{print $2}')
PHP_MAJOR=$(echo "$PHP_VERSION" | cut -d. -f1) # Extract major version
PHP_MINOR=$(echo "$PHP_VERSION" | cut -d. -f2) # Extract minor version

# Check if PHP version is exactly 5.6.x
if [[ "$PHP_MAJOR" -eq 5 && "$PHP_MINOR" -eq 6 ]]; then
    echo "PHP version is $PHP_VERSION (compatible)."
else
    echo "PHP version is $PHP_VERSION. Only PHP 5.6.x is supported."
    exit 1
fi

if [ ! -x "$COMPOSER_PATH" ]; then
    echo "Can't find or execute Composer on : $PHP_PATH $COMPOSER_PATH"
    exit 1
fi

# Get Composer version
COMPOSER_VERSION=$($COMPOSER_PATH --version | awk '{print $3}' | cut -d. -f1,2) # Extract major.minor version

# Check if Composer version is exactly 2.2.x
if [[ "$COMPOSER_VERSION" == "2.2" ]]; then
    echo "Composer version is $COMPOSER_VERSION (compatible)."
else
    echo "Composer version is $COMPOSER_VERSION. Only Composer 2.2.x (LTS) is supported."
    exit 1
fi

PATH_TO_COMPOSER="$PHP_PATH $COMPOSER_PATH"
echo "PATH_TO_COMPOSER is: $PATH_TO_COMPOSER"

# {{{ function usage
#
usage() {
    echo
    echo "This script builds an Alma woocommerce plugin ZIP archive and (optionally) sync files with subversion wordpress marketplace working copy."
    echo
    echo "USAGE: $0 [OPTIONS]"
    echo
    echo "WHERE available OPTIONS are:"
    echo "    --help | -h)        Prints this message and exit without error."
    echo "    --sync-svn | -s)    Activates sync Action between freshly built release and wordpress subversion working copy. (very simple for the moment)"
    echo "    --verbose | -v)     Prints all outputs (this script is pretty quiet by default)"
    echo
}
export -f usage
# }}}


# {{{ function quit
#
quit() {
    echo -e "$@"
    exit 1
}
export -f quit
# }}}


# {{{ shopt args
while [[ ! -z "$1" ]] ; do
    case "$1" in
        --sync-svn|-s) SYNC_SVN=1 ;;
        --verbose|-v)  QUIET=0;;
        --help|-h)     usage ; exit 0;;
        *) echo "'$1': unmanaged parameter" ; exit 1 ;;
    esac
    shift
done
# }}}

set -Eeuo pipefail

# {{{ CONSTANTS (working folders & files to sync)
HERE="`pwd`" # You should be in GIT root folder
SUBVERSION_DIR="$HERE/../alma_gateway_for_woocommerce_svn" # should be in the same parent folder and called alma_gateway_for_woocommerce_svn/
TMP_TARGET_DIR="/tmp/alma-build/alma-gateway-for-woocommerce"
DIST="$HERE/dist/"
TO_SYNC=" \
readme.txt \
LICENSE  \
./src/assets \
./src/build \
./src/includes \
./src/languages \
./src/public \
./src/tests \
./src/composer.json \
./src/phpcs.xml \
./src/alma-gateway-for-woocommerce.php \
./src/uninstall.php \
"
RSYNC_EXCLUDE="--exclude=*.orig --exclude=.DS_Store"
# }}}

# {{{ function is_that_ok
#
is_that_ok() {
    local code="${1:-0}"
    if [[ $code -eq 0 ]] ; then
        echo "OK"
        return 0
    fi
    echo "FAIL"
}
export -f is_that_ok
# }}}
# {{{ function failure
#
failure() {
    echo -n "UNCAUGHT FAILURE"
}
export -f failure
# }}}
# {{{ function execute
#
execute() {
    local cmd="$1"
    local quiet="${2:-$QUIET}"
    echo -n "${cmd//_/ } ... "
    if [[ $quiet -eq 1 ]] ; then
        $1 >/dev/null 2>&1
    else
        $1
    fi
    is_that_ok $?
}
export -f execute
# }}}

trap "failure" ERR EXIT

# {{{ function preparing_folders
#
preparing_folders() {
    [[ -d $DIST ]] && rm -rf $DIST
    mkdir -p $DIST
    [[ -d $TMP_TARGET_DIR ]] && rm -rf $TMP_TARGET_DIR
    mkdir -p $TMP_TARGET_DIR
}
export -f preparing_folders
# }}}
# {{{ function building_release
#
building_release() {
    rsync -au $TO_SYNC $RSYNC_EXCLUDE $TMP_TARGET_DIR/ \
        && cd $TMP_TARGET_DIR \
        && $PATH_TO_COMPOSER install --no-dev \
        && cd .. \
        && zip -9 -r "$DIST/alma-gateway-for-woocommerce.zip" alma-gateway-for-woocommerce
}
export -f building_release
# }}}
# {{{ function syncing_subversion
#
syncing_subversion() {
    if [[ ! -d "$SUBVERSION_DIR" ]] ; then
        trap - ERR EXIT
        is_that_ok 1
        echo "'$SUBVERSION_DIR': Subversion's folder not found!"
        echo "Please clone subversion repository with valid wordpress credentials (\`svn checkout https://plugins.svn.wordpress.org/alma-gateway-for-woocommerce $SUBVERSION_DIR\`)"
        exit 1
    fi
    cd $SUBVERSION_DIR
    svn update
    rm -rf ./trunk
    rsync -au $RSYNC_EXCLUDE $TMP_TARGET_DIR/ ./trunk >/dev/null 2>&1
    rm -rf $SUBVERSION_DIR/assets/*
    rsync -au $RSYNC_EXCLUDE $HERE/.wordpress.org/ ./assets >/dev/null 2>&1
    svn cp trunk tags/$VERSION
    files=$(svn status | awk '$1 ~ /\?/ {print $2}')
    if [ -n "$files" ]; then
        svn add $files || quit "Error occurs while adding subversion new files"
    else
        echo "No new files to add."
    fi
    files=$(svn status | awk '$1 ~ /\!/ {print $2}')
    if [ -n "$files" ]; then
        svn rm $files || quit "Error occurs while removing subversion files"
    else
        echo "No files to delete."
    fi
    cd -
}
export -f syncing_subversion
# }}}

VERSION=`grep "Stable tag" $HERE/readme.txt  | awk -F: '{print $2}' | sed 's/ //g'`
[ -z "$VERSION" ] && quit "VERSION not found in readme.txt (Stable tag)"

execute preparing_folders 0
execute building_release $QUIET
if [[ $SYNC_SVN -eq 1 ]] ; then
    execute syncing_subversion $QUIET
    echo
    echo "You can now go on GitHub to create a new release called 'v$VERSION' (https://github.com/alma/alma-woocommerce-gateway/releases/new)"
    echo "and add it the archive just created ($DIST/alma-gateway-for-woocommerce.zip)."
    echo
    echo "Then, you can now go into '$SUBVERSION_DIR' folder to finalize deployment on marketplace"
    echo
fi

trap - EXIT
