#!/usr/bin/env bash
set -Eeuo pipefail

DIR=`pwd`

rm -rf ./dist/
rm -rf /tmp/alma-build/alma-woocommerce-gateway
mkdir -p /tmp/alma-build/alma-woocommerce-gateway

TO_SYNC=" \
readme.txt \
LICENSE  \
./src/assets \
./src/includes \
./src/languages \
./src/tests \
./src/composer.json \
./src/phpcs.xml \
./src/alma-woocommerce-gateway.php \
./src/uninstall.php \
"
rsync -auv $TO_SYNC --exclude="*.orig" --exclude=".DS_Store" /tmp/alma-build/alma-woocommerce-gateway/

mkdir ./dist

cd /tmp/alma-build/alma-woocommerce-gateway
composer install --no-dev
cd ..
zip -9 -r "$DIR/dist/alma-woocommerce-gateway.zip" alma-woocommerce-gateway

rm -rf /tmp/alma-build
