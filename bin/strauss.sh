#!/bin/bash

# Exit immediately if a command exits with a non-zero status.
set -e

# Define a temporary directory for the build
BUILD_DIR="tmp-zip-export"
# Define the namespace prefix. Adjust if your strauss config is different.
NAMESPACE_PREFIX="Alma\\\\Vendor"

# OS-agnostic sed in-place flag
SED_I='-i'
if [[ "$OSTYPE" == "darwin"* ]]; then
  SED_I="-i ''"
fi

echo "=> Cleaning up previous build artifacts..."
rm -rf ./dist ./vendor-prefixed "$BUILD_DIR"

echo "=> Downloading Strauss phar..."
# Download the latest strauss.phar
curl -L -o strauss.phar https://github.com/BrianHenryIE/strauss/releases/latest/download/strauss.phar
chmod +x strauss.phar

echo "=> Prefixing dependencies and project source..."
# Run strauss. It will create vendor-prefixed with all dependencies and project files.
./strauss.phar

echo "=> Cleaning up Strauss..."
# Remove the phar file
rm strauss.phar

echo "=> Preparing files for packaging..."
# Create the temporary build directory
mkdir -p "$BUILD_DIR"
# Copy necessary files and directories to the build directory
cp alma-gateway-for-woocommerce.php "$BUILD_DIR/"
cp readme.txt "$BUILD_DIR/"
cp composer.json "$BUILD_DIR/"
cp -r build "$BUILD_DIR/"
cp -r includes "$BUILD_DIR/"
cp -r languages "$BUILD_DIR/"
cp -r public "$BUILD_DIR/"
cp -r assets "$BUILD_DIR/"

# Move the prefixed vendor directory into the build directory
mv vendor-prefixed "$BUILD_DIR/"

echo "=> Preparing composer.json for production..."
# Remove require and require-dev sections from the composer.json in the build directory
eval "sed $SED_I -e '/\"require\": {/,/}/d' -e '/\"require-dev\": {/,/}/d' \"$BUILD_DIR/composer.json\""

echo "=> Installing production autoloader..."
# Run composer install in the build directory to generate a clean vendor directory
(cd "$BUILD_DIR" && composer install --no-dev --optimize-autoloader)

echo "=> Patching autoloader path..."
# Modify the main plugin file to require both autoloaders.
eval "sed $SED_I \"s/require_once 'vendor\/autoload.php';/require_once 'vendor\/autoload.php';\\\nrequire_once 'vendor-prefixed\/autoload.php';/g\" \"$BUILD_DIR/alma-gateway-for-woocommerce.php\""

echo "=> Prefixing namespaces in source files..."
# Find all .php files in the copied sources and replace namespaces.
find "$BUILD_DIR/includes" "$BUILD_DIR/public" "$BUILD_DIR/alma-gateway-for-woocommerce.php" -type f -name "*.php" -exec \
bash -c "sed $SED_I -e 's/Alma\\\\Plugin\\\\/${NAMESPACE_PREFIX}\\\\Alma\\\\Plugin\\\\/g' -e 's/Alma\\\\Client\\\\/${NAMESPACE_PREFIX}\\\\Alma\\\\Client\\\\/g' -e 's/Psr\\\\Log\\\\/${NAMESPACE_PREFIX}\\\\Psr\\\\Log\\\\/g' -e 's/Psr\\\\Http\\\\/${NAMESPACE_PREFIX}\\\\Psr\\\\Http\\\\/g' -e 's/Dice\\\\/${NAMESPACE_PREFIX}\\\\Dice\\\\/g' {}" \;

echo "=> Creating distribution zip..."
mkdir -p dist
# Create the zip from the contents of the build directory
(cd "$BUILD_DIR" && zip -r ../dist/alma-gateway-for-woocommerce.zip .)

echo "=> Cleaning up temporary build directory..."
rm -rf "$BUILD_DIR"

echo "=> Build complete."