#!/usr/bin/env bash
set -Eeuo pipefail

DIR=`pwd`

rm -rf ./dist/
rm -rf /tmp/alma-build/alma-gateway-for-woocommerce
mkdir -p /tmp/alma-build/alma-gateway-for-woocommerce

TO_SYNC=" \
readme.txt \
LICENSE  \
./src/assets \
./src/includes \
./src/languages \
./src/tests \
./src/composer.json \
./src/phpcs.xml \
./src/alma-gateway-for-woocommerce.php \
./src/uninstall.php \
"
rsync -auv $TO_SYNC --exclude="*.orig" --exclude=".DS_Store" /tmp/alma-build/alma-gateway-for-woocommerce/

mkdir ./dist

cd /tmp/alma-build/alma-gateway-for-woocommerce
composer install --no-dev
cd ..
zip -9 -r "$DIR/dist/alma-gateway-for-woocommerce.zip" alma-gateway-for-woocommerce

rm -rf /tmp/alma-build
