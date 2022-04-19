#!/bin/bash

# Author : Gilles Dumas
# Date : 20220307
# This script retrieves
# -- the JS unminified file to include in web pages who display the alma widget.
# -- the CSS file.
# -- the fonts.  (todo not sure)

files_url='https://cdn.jsdelivr.net/npm/@alma/widgets@2.x/dist/';
raw_url=${files_url}'raw/';

# the directory_prefix is the relative folder path where assets are called in the plugin via the wp_enqueue_script() function.
assets_directory_prefix='../src/assets/';

js_file_name='widgets.umd.js';
js_file_url=${raw_url}${js_file_name};

css_file_name='widgets.min.css';
css_file_url=${raw_url}${css_file_name};

font_files_base_url=${files_url}'assets/fonts/';

js_directory_prefix=${assets_directory_prefix}'js/';
css_directory_prefix=${assets_directory_prefix}'css/';
fonts_directory_prefix=${css_directory_prefix}'assets/fonts/';

# retrieve fonts.
declare -a font_files_array
font_files_array=(Eina04-Bold.ttf Eina04-Bold.woff PublicSans-VariableFont_wght.ttf);
for font_file in "${font_files_array[@]}"; do
  wget -dc ${font_files_base_url}${font_file};
  mv "${font_file}" ${fonts_directory_prefix}
done;

# retrieve JS.
wget -dc ${js_file_url};
mv ${js_file_name} ${js_directory_prefix}

# retrieve CSS.
wget -dc ${css_file_url};
mv ${css_file_name} ${css_directory_prefix}

