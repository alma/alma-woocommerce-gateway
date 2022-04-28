#!/bin/bash

# Author : Gilles Dumas
# Date : 20220426
# This script retrieves
# -- the JS unminified file to include in web pages to display the Alma widget.
# -- the CSS file.
# -- the fonts.

DIST_URL="https://cdn.jsdelivr.net/npm/@alma/widgets@2.x/dist"
RAW_URL="${DIST_URL}/raw"
ROOT_DIR="`dirname $0`/.."
WIDGET_ASSETS_DIR="${ROOT_DIR}/src/assets/widget"

mkdir -p ${WIDGET_ASSETS_DIR}/{css/fonts,js}

RAW_FILES="
css/widgets.css
js/widgets.umd.js
js/widgets.umd.js.map
"
FONT_FILES="
css/fonts/Eina04-Bold.ttf
css/fonts/Eina04-Bold.woff
css/fonts/PublicSans-VariableFont_wght.ttf
"

for raw_file in ${RAW_FILES} ; do
    echo loading ${raw_file}
    file_name="`basename ${raw_file}`"
    curl ${RAW_URL}/${file_name} > ${WIDGET_ASSETS_DIR}/${raw_file} 2>/dev/null
done
for font_file in $FONT_FILES ; do
    echo loading ${font_file}
    curl ${DIST_URL}/${font_file} > ${WIDGET_ASSETS_DIR}/${font_file} 2>/dev/null
done

exit
