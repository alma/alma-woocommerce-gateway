#!/usr/bin/env bash
#TODO: svn checkout, svn commit (if needed) after svn sync
QUIET=1
SYNC_SVN=0
DEBUG=0

# {{{ function usage
#
usage() {
    echo
    echo "This script uses an existing Alma woocommerce plugin ZIP archive (built by 'task 7.4:dist') and (optionally) syncs files with subversion wordpress marketplace working copy."
    echo
    echo "USAGE: $0 [OPTIONS]"
    echo
    echo "WHERE available OPTIONS are:"
    echo "    --help | -h)        Prints this message and exit without error."
    echo "    --sync-svn | -s)    Activates sync Action between freshly built release and wordpress subversion working copy. (very simple for the moment)"
    echo "    --verbose | -v)     Prints all outputs (this script is pretty quiet by default)"
    echo "    --debug | -d)       Enable debug mode with detailed error information."
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
while [[ -n "${1:-}" ]] ; do
    case "$1" in
        --sync-svn|-s) SYNC_SVN=1 ;;
        --verbose|-v)  QUIET=0;;
        --debug|-d)    DEBUG=1;;
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
ZIP_FILE="${DIST}alma-gateway-for-woocommerce.zip"
RSYNC_EXCLUDE="--exclude=\*.orig --exclude=.DS_Store"
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
    local exit_code=$?
    local line_number=${1:-unknown}

    if [[ $DEBUG -eq 1 ]]; then
        echo
        echo "=========================================="
        echo "ERROR: Script failed at line $line_number"
        echo "Exit code: $exit_code"
        echo "Last command: $BASH_COMMAND"
        echo "=========================================="
        echo
        # Print call stack for better debugging
        local frame=0
        while caller $frame; do
            ((frame++))
        done
    else
        echo "UNCAUGHT FAILURE (use --debug for details)"
    fi
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

trap 'failure ${LINENO}' ERR

# {{{ function preparing_folders
#
preparing_folders() {
    [[ -d $TMP_TARGET_DIR ]] && rm -rf $TMP_TARGET_DIR
    mkdir -p $(dirname "$TMP_TARGET_DIR")
}
export -f preparing_folders
# }}}
# {{{ function building_release
#
building_release() {
    if [[ ! -f "$ZIP_FILE" ]]; then
        quit "ZIP archive not found at '$ZIP_FILE'. Please run 'task 7.4:dist' first."
    fi
    echo "Using existing ZIP archive: $ZIP_FILE"
    unzip -o "$ZIP_FILE" -d $(dirname "$TMP_TARGET_DIR")
    if [[ ! -d "$TMP_TARGET_DIR" ]]; then
         quit "Expected directory '$TMP_TARGET_DIR' not found after unzip. Check the ZIP structure."
    fi
}
export -f building_release
# }}}
# {{{ function syncing_subversion
#
syncing_subversion() {
    if [[ ! -d "$SUBVERSION_DIR" ]] ; then
        trap - ERR
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
    echo "and add it the archive just created ($ZIP_FILE)."
    echo
    echo "Then, you can now go into '$SUBVERSION_DIR' folder to finalize deployment on marketplace"
    echo
fi

