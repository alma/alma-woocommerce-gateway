#!/bin/bash
vendor/bin/phpcs --standard=phpcs.xml ./
if [ $? != 0 ]
then
  echo "Fix the errors before commit!"
  exit 1
fi
