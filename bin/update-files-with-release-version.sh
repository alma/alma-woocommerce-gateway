#!/usr/bin/env bash

###############################################################################
# README
###############################################################################
# Description :
#     Script to be used in the release process to update the version
#     number in the files
# Usage :
#     ./scripts/update-files-with-release-version.sh <version>
#     (Example: ./scripts/update-files-with-release-version.sh 1.2.3)
###############################################################################

if [ $# -lt 1 ]; then
  echo 1>&2 "$0: Missing argument. Please specify a version number."
  exit 2
fi

####################
# Sanitize version (remove the 'v' prefix if present)
####################
version=`echo ${1#v}`


####################
# Update file ./README.md
####################
filepath="./README.md"
# Update "Stable tag" info
sed -i -E "s/Stable tag: [0-9\.]+/Stable tag: $version/g" $filepath

####################
# Update file ./readme.txt
####################
filepath="./readme.txt"
# Update "Stable tag" info
sed -i -E "s/Stable tag: [0-9\.]+/Stable tag: $version/g" $filepath
# Retrieve changelog of current version from CHANGELOG.md
changelog=$(sed -n "/^## v$version/,/^## v/p" CHANGELOG.md | head -n -1)
# Do same thing than the previous line, but keep the first line matching "## v$version" in the output
changelog=$(echo "$changelog" | sed ':a;N;$!ba;s/\n/\\n/g')
sed -i -E "/== Changelog ==/a \\\n$changelog" $filepath

####################
# Update file ./src/alma-gateway-for-woocommerce.php
####################
filepath="./src/alma-gateway-for-woocommerce.php"
# Update "ALMA_VERSION" constant
sed -i -E "s/'ALMA_VERSION', '[0-9\.]+'/'ALMA_VERSION', '$version'/g" $filepath
# Update "Version" info
sed -i -E "s/\* Version: [0-9\.]+/* Version: $version/g" $filepath
