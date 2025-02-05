#!/bin/bash
vendor/bin/phpcbf --standard=phpcs.xml ./
if [ $? != 0 ]
then
  echo "Fix the errors with PHPcbf automatic fixer before commit!"
  exit 1
fi
