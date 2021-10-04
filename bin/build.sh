#!/usr/bin/env bash
set -Eeuo pipefail

DIR=`pwd`

rm -rf ./dist/
rm -rf /tmp/alma-build/alma-woocommerce-gateway
mkdir -p /tmp/alma-build/alma-woocommerce-gateway

cp -r ./src/* readme.txt LICENSE /tmp/alma-build/alma-woocommerce-gateway/

mkdir ./dist

cd /tmp/alma-build/alma-woocommerce-gateway
rm -rf vendor
composer install --no-dev
cd ..
zip -9 -r "$DIR/dist/alma-woocommerce-gateway.zip" alma-woocommerce-gateway --exclude "*/.*" "*/build.sh" "*/dist" "*/docker*"

rm -rf /tmp/alma-build
