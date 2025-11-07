#!/bin/bash
#
# Update CMS compatibility versions in README files
#
# Usage: ./bin/update-cms-compatibility.sh <woocommerce-version> <wordpress-version>
#
# Example: ./bin/update-cms-compatibility.sh 10.2.1 6.8.2

set -e

# Check if the correct number of arguments are provided
if [ "$#" -ne 2 ]; then
    echo "Usage: $0 <woocommerce-version> <wordpress-version>"
    echo "Example: $0 10.2.1 6.8.2"
    exit 1
fi

CMS_VERSION="$1"
WP_VERSION="$2"

echo "Updating CMS compatibility versions..."
echo "  WooCommerce: ${CMS_VERSION}"
echo "  WordPress: ${WP_VERSION}"

# Update README.md
echo "Updating README.md..."
sed -i "s/^- Tested up to Wordpress: .*/- Tested up to Wordpress: ${WP_VERSION}/" README.md
sed -i "s/^- Tested up to Woocommerce: .*/- Tested up to Woocommerce: ${CMS_VERSION}/" README.md

# Update readme.txt
echo "Updating readme.txt..."
sed -i "s/^Tested up to Wordpress: .*/Tested up to Wordpress: ${WP_VERSION}/" readme.txt
sed -i "s/^Tested up to Woocommerce: .*/Tested up to Woocommerce: ${CMS_VERSION}/" readme.txt

# Update alma-gateway-for-woocommerce.php
echo "Updating alma-gateway-for-woocommerce.php..."
sed -i "s/^ \* Tested up to: .*/ \* Tested up to: ${WP_VERSION}/" alma-gateway-for-woocommerce.php
sed -i "s/^ \* WC tested up to: .*/ \* WC tested up to: ${CMS_VERSION}/" alma-gateway-for-woocommerce.php

echo "✓ CMS compatibility versions updated successfully!"
