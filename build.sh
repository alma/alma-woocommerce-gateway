#!/usr/bin/env bash

DIR=`pwd`

rm -rf ./dist/
rm -rf /tmp/alma-build/alma-woocommerce-gateway
mkdir -p /tmp/alma-build/alma-woocommerce-gateway

cp -r ./* /tmp/alma-build/alma-woocommerce-gateway/

mkdir ./dist

cd /tmp/alma-build/alma-woocommerce-gateway
rm vendor -r
composer install --no-dev
cd ..
zip -9 -r "$DIR/dist/alma-woocommerce-gateway.zip" alma-woocommerce-gateway --exclude "*/.*" "*/build.sh" "*/dist" "*/docker*"

rm -rf /tmp/alma-build
