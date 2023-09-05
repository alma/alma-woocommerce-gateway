#!/bin/bash
/usr/bin/php5.6 src/vendor/bin/phpcs --standard=src/phpcs.xml src/
if [ $? != 0 ]
then
  echo "Fix the errors before commit!"
  exit 1
fi
