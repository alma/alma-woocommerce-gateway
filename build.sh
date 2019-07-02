#!/usr/bin/env bash

DIR=`pwd`

rm -rf ./dist/
rm -rf /tmp/alma-build/alma-woocommerce-gateway
mkdir -p /tmp/alma-build/alma-woocommerce-gateway

cp -r ./* /tmp/alma-build/alma-woocommerce-gateway/

mkdir ./dist

cd /tmp/alma-build/
zip -9 -r "$DIR/dist/alma-woocommerce-gateway.zip" alma-woocommerce-gateway --exclude \*dist\* \*.git\* \*.idea\* \*.DS_Store

rm -rf /tmp/alma-build
