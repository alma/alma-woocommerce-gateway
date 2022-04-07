#!/usr/bin/bash

# Author : Gilles Dumas
# Date : 20220307
# This script retrieves the js file to include in web pages who display the alma widget.
# This js file is supposed to be unminified.
# So the url below will need to be changed as soon as the unminified file will be reachable.

file_url='https://cdn.jsdelivr.net/npm/@alma/widgets@2.x/dist/widgets.umd.js';
file_name='widgets.umd.js';

# the directory_prefix is the relative folder path where js resources are called in the plugin via the wp_enqueue_script() function.
directory_prefix='../src/assets/js/';

wget -vdc ${file_url};
mv ${file_name} ${directory_prefix}

