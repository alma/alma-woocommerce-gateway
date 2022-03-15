#!/bin/bash
set -Eeo pipefail

SEP_CHANGELOG="--------------------------------------------------------------------------------"
SEP_README="================================================================================"
VERSION="$1"

# {{{ function quit
#
quit() {
    echo -e "ERROR: $1" >&2
    exit 1
}
export -f quit
# }}}

# provide your own SED_BIN as env variable if you want (must be compatible with gnu sed version)
if [[ -z "$SED_BIN" ]] ; then
    SED_BIN="/usr/bin/sed"
    if [[ "`uname -s`" = "Darwin" ]] ; then
        SED_BIN="/usr/local/bin/gsed"
    fi
fi

if [[ ! -x "$SED_BIN" ]] ; then
    quit "sed binary does not exists or is not executable ($SED_BIN) => please define a SED_BIN env variable (must be compatible with gnu sed version)."
fi

LATEST_VERSION="`git tag --list | grep "^v" | sort -n | tail -n1 | $SED_BIN 's/[[:blank:]]*//g'`"
[[ -z "$VERSION" ]] && quit "provide version as first arg"
[[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || quit "version provided ($VERSION) does not respect expected one => x.x.x (@see https://semver.org/)"
[[ -z "$LATEST_VERSION" ]] && quit "no latest version found. git tag --list return empty version or pattern does not respect the expected one => vx.x.x (@see https://semver.org/)"
[[ "$LATEST_VERSION" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]] || quit "bad latest version found ($LATEST_VERSION). git tag --list does not return a valid version pattern like vx.x.x (@see https://semver.org/)"

# {{{ function git_log_since_latest
#
git_log_since_latest() {
    git log HEAD --not $LATEST_VERSION \
        | grep -E "^[[:blank:]]*(doc|feat|fix|ci|word|refactor)[a-z()-_]*:" \
        | sed 's/^[[:blank:]]*/* /g' \
        | sort
}
export -f git_log_since_latest
# }}}


# BUILD LOCAL CHANGELOG
TMP_CHANGELOG="`mktemp /tmp/changelog.XXX`"
echo -e "v$VERSION\n${SEP_CHANGELOG:0:$((${#VERSION}+1))}\n#" > $TMP_CHANGELOG
git_log_since_latest >> $TMP_CHANGELOG
echo "#" >> $TMP_CHANGELOG

# BUILD LOCAL README
TMP_README="`mktemp /tmp/readme.XXX`"
echo -e "= $VERSION =\n${SEP_README:0:$((${#VERSION}+4))}\n#" > $TMP_README
git_log_since_latest >> $TMP_README
echo "#" >> $TMP_README


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

update_changelog $TMP_CHANGELOG CHANGELOG.md $LATEST_VERSION
update_changelog $TMP_README readme.txt "= ${LATEST_VERSION/v/} ="

bump_stable_tag README.md ${LATEST_VERSION/v/} $VERSION
bump_stable_tag readme.txt ${LATEST_VERSION/v/} $VERSION

bump_version src/alma-woocommerce-gateway.php ${LATEST_VERSION/v/} $VERSION
git add --patch
git commit --message "chore: bump version $VERSION"
git commit --amend
git tag v$VERSION
rm $TMP_README $TMP_CHANGELOG
