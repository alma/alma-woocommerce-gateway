#!/bin/bash
src/vendor/bin/phpcbf --standard=phpcs.xml src/
src/vendor/bin/phpcs --standard=phpcs.xml src/
src/vendor/bin/phpcs -p src/ --standard=PHPCompatibility -s --runtime-set testVersion 5.6-8.1 --ignore=\*/src/vendor/\*