#!/bin/bash
/usr/bin/php5.6 src/vendor/bin/phpcs -p src/ --standard=PHPCompatibility -s --runtime-set testVersion 5.6-8.1 --ignore=\*/src/vendor/\*
if [ $? != 0 ]
then
  echo "Fix Compatibilities errors before commit!"
  exit 1
fi