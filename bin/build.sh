#!/usr/bin/env bash
set -Eeuo pipefail
QUIET=1

# {{{ CONSTANTS (working folders & files to sync)
HERE="`pwd`" # You should be in GIT root folder
SUBVERSION_DIR="$HERE/../alma_gateway_for_woocommerce_svn" # should be in the same parent folder and called alma_gateway_for_woocommerce_svn/
TMP_TARGET_DIR="/tmp/alma-build/alma-gateway-for-woocommerce"
DIST="$HERE/dist/"
TO_SYNC=" \
readme.txt \
LICENSE  \
./src/assets \
./src/includes \
./src/languages \
./src/tests \
./src/composer.json \
./src/phpcs.xml \
./src/alma-gateway-for-woocommerce.php \
./src/uninstall.php \
"
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
    if [[ ! -d "$SUBVERSION_DIR" ]] ; then
        trap - ERR EXIT
        is_that_ok 1
        echo "'$SUBVERSION_DIR': Subversion's folder not found !!!"
        echo "Please clone subversion repository with valid wordpress credentials (\`svn checkout https://plugins.svn.wordpress.org/alma-gateway-for-woocommerce $SUBVERSION_DIR\`)"
        exit 1
    fi
}
export -f preparing_folders
# }}}
# {{{ function building_release
#
building_release() {
    rsync -au $TO_SYNC --exclude="*.orig" --exclude=".DS_Store" $TMP_TARGET_DIR/ \
        && cd $TMP_TARGET_DIR \
        && composer install --no-dev \
        && cd .. \
        && zip -9 -r "$DIST/alma-gateway-for-woocommerce.zip" alma-gateway-for-woocommerce
}
export -f building_release
# }}}
# {{{ function syncing_subversion
#
syncing_subversion() {
    rm -rf $SUBVERSION_DIR/trunk
    rsync -au $TMP_TARGET_DIR $SUBVERSION_DIR/trunk >/dev/null 2>&1
}
export -f syncing_subversion
# }}}

execute preparing_folders 0
execute building_release $QUIET
execute syncing_subversion $QUIET
echo "You can now go into '$SUBVERSION_DIR' folder to finalize deployment on marketplace"

trap - EXIT
