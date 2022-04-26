#!/bin/bash

# Author : Gilles Dumas
# Date : 20220426
# This script retrieves
# -- the JS unminified file to include in web pages to display the Alma widget.
# -- the CSS file.
# -- the fonts.

files_url='https://cdn.jsdelivr.net/npm/@alma/widgets@2.x/dist/';
raw_url=${files_url}'raw/';

# Relative folder paths where assets are loaded in the plugin via the wp_enqueue_script() function.
widget_directory_prefix='../src/assets/widget/';
js_directory_prefix=${widget_directory_prefix}'js/';
css_directory_prefix=${widget_directory_prefix}'css/';
assets_directory_prefix=${css_directory_prefix}'assets/';
fonts_directory_prefix=${assets_directory_prefix}'fonts/';

for directory in $widget_directory_prefix $js_directory_prefix $css_directory_prefix $assets_directory_prefix $fonts_directory_prefix
do
  if [[ -d "$directory" ]] ; then
    echo "$directory already exists on your filesystem. (but this is not a problem)"
  else
    mkdir $directory;
  fi
done

js_file_name='widgets.umd.js';
js_file_url=${raw_url}${js_file_name};
js_map_file_name='widgets.umd.js.map';
js_map_file_url=${raw_url}${js_map_file_name};

css_file_name='widgets.min.css';
css_file_url=${raw_url}${css_file_name};

font_files_base_url=${files_url}'assets/fonts/';

# --1-- retrieve fonts.
declare -a font_files_array
font_files_array=(Eina04-Bold.ttf Eina04-Bold.woff PublicSans-VariableFont_wght.ttf);
for font_file in "${font_files_array[@]}"; do
  wget -dc ${font_files_base_url}${font_file};
  mv "${font_file}" ${fonts_directory_prefix}
done;

# --2-- retrieve JS.
wget -dc ${js_file_url};
mv ${js_file_name} ${js_directory_prefix}

# --3-- retrieve JS map.
wget -dc ${js_map_file_url};
mv ${js_map_file_name} ${js_directory_prefix}

# --4-- retrieve CSS.
wget -dc ${css_file_url};
mv ${css_file_name} ${css_directory_prefix}

exit;