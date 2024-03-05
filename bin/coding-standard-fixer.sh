#!/bin/bash
php src/vendor/bin/phpcbf --standard=src/phpcs.xml src/
if [ $? != 0 ]
then
  echo "Fix the errors with PHPcbf automatic fixer before commit!"
  exit 1
fi
