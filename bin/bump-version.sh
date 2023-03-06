#!/bin/bash

SEP_CHANGELOG="--------------------------------------------------------------------------------"
TARGET_VERSION=""
FROM_VERSION=""
LATEST_VERSION="``"

# DECLARE USEFULL FUNCTIONS & BINARIES
# {{{ function quit
#
quit() {
    echo
    echo -e "ERROR: $1" >&2
    echo
    usage
    exit ${2:-1}
}
export -f quit
# }}}
#{{{ provide your own SED_BIN as env variable if you want (must be compatible with gnu sed version)
if [[ -z "$SED_BIN" ]] ; then
    SED_BIN="/usr/bin/sed"
    if [[ "`uname -s`" = "Darwin" ]] ; then
        SED_BIN="/usr/local/bin/gsed"
    fi
fi
if [[ ! -x "$SED_BIN" ]] ; then
    quit "sed binary does not exists or is not executable ($SED_BIN) => please define a SED_BIN env variable (must be compatible with gnu sed version)."
fi
# }}}
# {{{ function usage
#
usage() {
    echo
    echo "USAGE: $0 TARGET_VERSION [FROM_VERSION]"
    echo "   This script will create a version starting by 'v' (ie: v2.7.3),"
    echo "   build a changelog, readme, change plugin version and allow you to edit git add --patch + commit messages"
    echo
    echo "EXEMPLES:"
    echo "   ~$ $0 2.7.3"
    echo "   ~$ $0 2.7.3 2.7.1"
    echo
    echo "NOTES:"
    echo "   if FROM_VERSION is ommitted, latest version is searched with \`git tag --list | sort\` command."
    echo
}
export -f usage
# }}}
# {{{ function get_latest_version
#
get_latest_version() {
    git tag --list | grep "^v" | sort -n | tail -n1 | $SED_BIN 's/[[:blank:]]*//g'
}
export -f get_latest_version
# }}}

# GET ARGS
while [[ ! -z "$1" ]] ; do
    case $1 in
        -h|--help) usage ; exit 0 ;;
        *)
            if [[ -z "$TARGET_VERSION" ]] ; then
                TARGET_VERSION="$1"
            elif [[ -z "$FROM_VERSION" ]] ; then
                FROM_VERSION="v$1"
            else
                quit "$0 takes only 2 args, what do you want to do with '$1' now?"
            fi
        ;;
    esac
    shift
done
set -Eueo pipefail

# TEST ARGS
[[ -z "$TARGET_VERSION" ]] && quit "You must provide a valid TARGET_VERSION as first arg"
[[ "$TARGET_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || quit "TARGET_VERSION provided ($TARGET_VERSION) does not respect expected format => x.x.x (@see https://semver.org/)"
if [[ -z "$FROM_VERSION" ]] ; then
    FROM_VERSION="`get_latest_version`"
    [[ -z "$FROM_VERSION" ]] && quit "FROM_VERSION not found. git \`tag --list\` returns an empty version or pattern does not respect the expected one => vx.x.x (@see https://semver.org/)"
    [[ "$FROM_VERSION" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]] || quit "FROM_VERSION: ($FROM_VERSION) \`git tag --list\` does not respect expected format => vx.x.x (@see https://semver.org/)"
else
    [[ "$FROM_VERSION" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]] || quit "FROM_VERSION: provided ($FROM_VERSION) does not respect expected format => vx.x.x (@see https://semver.org/)"
fi

# CREATE TMP FILES
TMP_CHANGELOG="`mktemp /tmp/changelog.XXXXX`"
TMP_README="`mktemp /tmp/readme.XXXXX`"

# DECLARE SCRIPT FUNCTIONS
# {{{ function git_log_since_latest
#
git_log_since_latest() {
    git log HEAD --not $FROM_VERSION \
        | grep -E "^[[:blank:]]*(doc|hotfix|feat|fix|ci|word|refactor)[a-z()-_]*:" \
        | $SED_BIN 's/^[[:blank:]]*/* /g' \
        | sort
}
export -f git_log_since_latest
# }}}
# {{{ function update_changelog
#
# @param $1 string from_file
# @param $2 string to_file
# @param $3 string from_version
#
update_changelog() {
    local from_file="$1"
    local to_file="$2"
    local from_version="$3"
    local line_num=`grep -n "$from_version" $to_file | awk -F: '{print $1}'`

    while read line ; do
        $SED_BIN -i "$line_num i $line" $to_file
        let line_num++
    done < $from_file
    $SED_BIN -i 's/^#$//g' $to_file
}
export -f update_changelog
# }}}
# {{{ function bump_stable_tag
#
# @param $1 string file_to_bump
# @param $2 string from_version
# @param $3 string to_version
#
bump_stable_tag() {
    local file_to_bump="$1"
    local from_version="$2"
    local to_version="$3"
    $SED_BIN -i "s/^Stable tag: $from_version/Stable tag: $to_version/" $file_to_bump
}
export -f bump_stable_tag
# }}}
# {{{ function bump_version
#
# @param $1 string file_to_bump
# @param $2 string from_version
# @param $3 string to_version
#
bump_version() {
    local file_to_bump="$1"
    local from_version="$2"
    local to_version="$3"
    $SED_BIN -i "s/$from_version/$to_version/" $file_to_bump
}
export -f bump_version
# }}}
# {{{ function edit_change_log
#
edit_change_log() {
    local changelog_file="$1"
    local readme_file="$2"
    ${EDITOR:-vi} $changelog_file
}
export -f edit_change_log
# }}}
# {{{ function copy_readme_from_changelog
#
copy_readme_from_changelog() {
    local readme_file="$1"
    local changelog_file="$2"
    local count_line=`wc -l $changelog_file| awk '{print $1}'`
    title_readme > $readme_file
    tail -n$(($count_line-3)) $changelog_file >> $readme_file
}
export -f copy_readme_from_changelog
# }}}
# {{{ function title_readme
#
title_readme() {
    echo -e "= $TARGET_VERSION ="
}
export -f title_readme
# }}}
# {{{ function title_changelog
#
title_changelog() {
    echo -e "v$TARGET_VERSION\n${SEP_CHANGELOG:0:$((${#TARGET_VERSION}+1))}\n#"
}
export -f title_changelog
# }}}
# {{{ function to_bump_or_not_to_bump
#
to_bump_or_not_to_bump() {
    echo "We will bump version 'v$TARGET_VERSION' (built from '$FROM_VERSION') with following changelog:"
    echo
    cat $TMP_CHANGELOG
    echo
    echo -n "Are you OK ? (Y/n/e - Yes/no/edit) "
    read resp
    case $resp in
        [yY]|[Yy][Ee][Ss]|"") return 0 ;;
        [eE]|[Ee][dD][Ii][Tt]) edit_change_log $TMP_CHANGELOG $TMP_README && to_bump_or_not_to_bump || return 1 ;;
        [nN]|[nN][oO]) return 1;;
        *) to_bump_or_not_to_bump ;;
    esac
}
export -f to_bump_or_not_to_bump
# }}}

# BUILD LOCAL CHANGELOG & README
title_changelog > $TMP_CHANGELOG
git_log_since_latest >> $TMP_CHANGELOG
echo "#" >> $TMP_CHANGELOG

# START THE BUMP
to_bump_or_not_to_bump || quit "Bump halted manually" 0
copy_readme_from_changelog $TMP_README $TMP_CHANGELOG
update_changelog $TMP_CHANGELOG CHANGELOG.md $FROM_VERSION
update_changelog $TMP_README readme.txt "= ${FROM_VERSION/v/} ="

bump_stable_tag README.md ${FROM_VERSION/v/} $TARGET_VERSION
bump_stable_tag readme.txt ${FROM_VERSION/v/} $TARGET_VERSION

bump_version src/alma-gateway-for-woocommerce.php ${FROM_VERSION/v/} $TARGET_VERSION
git add --patch
git commit --message "chore: bump version v$TARGET_VERSION"
git commit --amend
#git tag v$TARGET_VERSION
#rm $TMP_README $TMP_CHANGELOG
