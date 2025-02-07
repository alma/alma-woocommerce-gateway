#!/bin/bash
# This script retrieves
# -- the JS unminified file to include in web pages to display the Alma widget.
# -- the CSS file.


#{{{ SCRIPT CONSTANTS
ROOT_DIR="`dirname $0`/.."
BUILD_DIR="$ROOT_DIR/dist/widgets"
WIDGET_ASSETS_DIR="${ROOT_DIR}/assets/widget"
WIDGET_CSS_DIR="${ROOT_DIR}/assets/widget/css"

CDN_DIST_URL="https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist"
CDN_RAW_URL="${CDN_DIST_URL}/raw"

GIT_URL="https://github.com/alma/widgets"
GIT_WC_DIST_DIR="${BUILD_DIR}/dist"
GIT_WC_RAW_DIR="${GIT_WC_DIST_DIR}/raw"


RAW_FILES="
css/widgets.css
js/widgets-wc.umd.js
js/widgets-wc.umd.js.map
"
#}}}

# Common function
# {{{ function quit
#
quit() {
    echo
    echo -e "ERROR: $@"
    usage
    exit 1
}
export -f quit
# }}}
# {{{ function usage
#
usage() {
    echo
    echo "USAGE: $0 [OPTIONS]"
    echo
    echo "WHERE OPTIONS could be one of the following ones:"
    echo "   --help | -h)                          Prints this message and exit without errors."
    echo "   --from-git-branch | -G) GIT_BRANCH    Gives the GIT_BRANCH you want to sync (default is '${DEFAULT_GIT_BRANCH}')."
    echo "   --from-cdn | -C)                      Tells you want sync from CDN (not GIT) - This is the default behavior."
    echo "   --without-build)                      Don't reload from GIT nor re-build with npm if working copy already exists in '${BUILD_DIR}'."
    echo
    echo "Notes:"
    echo "   --from-cdn is the default behavior and '$CDN_DIST_URL' is the default CDN url at this time."
    echo "   --from-git-branch will sync & makes npm build by default (you should use --without-build if you want avoid this behavior)."
    echo
    echo "Version of NPM 8.6 is required to build the widget project"
    echo
}
export -f usage
# }}}
# {{{ function set_var_for
#
# @param var_for (the context of option or argument where var is set)
# @param var_name (the variable name)
# @param var_value (the value)
#
set_var_for() {
    local var_for var_name var_value current_value
    var_for="$1"
    var_name="$2"
    var_value="$3"
    [[ -z "$var_for" ]] && quit "'var_for' must be set when \`set_var_for\` is called" # for developer
    [[ -z "$var_name" ]] && quit "'var_name' must be set when \`set_var_for\` is called for '$var_for'" # for developer
    [[ -z "$var_value" ]] && quit "'$var_name' must be set with '$var_for'" # for script user
    current_value="${!var_name}"
    [[ ! -z "$current_value" ]] && quit "'$var_name' already set ($current_value) for '$var_for' - this option should be called only once." # for script user
    eval "export $var_name='$var_value'"
}
export -f set_var_for
# }}}

#{{{ Getting & Setting default ARGS
DEFAULT_SYNC_FROM="CDN"
DEFAULT_GIT_BRANCH="master"
DEFAULT_BUILD=1
while [[ ! -z "$1" ]] ; do
    case $1 in
        --help|-h) usage ; exit 0 ;;
        --from-git-branch|-G) set_var_for "$1" GIT_BRANCH "$2" ; set_var_for "$1" SYNC_FROM "GIT" ; shift ;;
        --without-build) set_var_for "$1" BUILD 0 ; shift ;;
        --from-cdn|-C) set_var_for "$1" SYNC_FROM "CDN" ; shift ;;
        *) quit "'$1' is not a valid option" ;;
    esac
    shift
done

[[ -z "$SYNC_FROM" ]] && SYNC_FROM="$DEFAULT_SYNC_FROM"
[[ -z "$GIT_BRANCH" ]] && GIT_BRANCH="$DEFAULT_GIT_BRANCH"
[[ -z "$BUILD" ]] && BUILD="$DEFAULT_BUILD"
#}}}

# Script functions
# {{{ function should_i_build
#
should_i_build() {
    [[ ! -d "${GIT_WC_RAW_DIR}" ]] || [[ "${BUILD}" -eq 1 ]]
}
export -f should_i_build
# }}}
# {{{ function sync_git_and_build
#
sync_git_and_build() {
    [[ -d "$BUILD_DIR" ]] && rm -rf $BUILD_DIR
    mkdir -p $BUILD_DIR
    echo "Preparing Widget GIT working copy"
    git clone --depth 1 --branch $GIT_BRANCH $GIT_URL $BUILD_DIR
    cd $BUILD_DIR
    echo "Building Widget dist & raw files"
    npm install
    npm run build
    cd - >/dev/null 2>&1
}
export -f sync_git_and_build
# }}}
# {{{ function sync_raw_file
#
sync_raw_file() {
    local from_file raw_file
    raw_file="$1"
    from_file="`basename ${raw_file}`"
    echo "Loading raw file '${raw_file}' from ${SYNC_FROM}"
    if [[ "${SYNC_FROM}" = "CDN" ]] ; then
        curl ${CDN_RAW_URL}/${from_file} > ${WIDGET_ASSETS_DIR}/${raw_file} 2>/dev/null
    else
        cat ${GIT_WC_RAW_DIR}/${from_file} > ${WIDGET_ASSETS_DIR}/${raw_file}
    fi
}
export -f sync_raw_file
# }}}

set -Eeuo pipefail

# Running Script
echo "Preparing Widget folders ..."
mkdir -p $WIDGET_CSS_DIR ${WIDGET_ASSETS_DIR}/{css,js}
find $WIDGET_CSS_DIR ${WIDGET_ASSETS_DIR}/{css,js} -type f -exec rm {} \;

if [[ $SYNC_FROM = "GIT" ]] && ( should_i_build ) ; then
    sync_git_and_build
fi

for raw_file in ${RAW_FILES} ; do
    sync_raw_file "${raw_file}"
done

exit 0
