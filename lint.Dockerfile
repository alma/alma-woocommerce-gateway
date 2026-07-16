ARG PHP_IMG_TAG=8.1-fpm

FROM composer:latest AS composer

FROM php:${PHP_IMG_TAG} AS production

WORKDIR /composer

# Set Environment Variables
ENV DEBIAN_FRONTEND=noninteractive

RUN set -eux; apt-get update; apt-get upgrade -y; apt-get install -y --no-install-recommends curl
RUN set -eux; apt-get update; apt-get upgrade -y; apt-get install -y --no-install-recommends libcurl4-openssl-dev
RUN set -eux; apt-get update; apt-get upgrade -y; apt-get install -y --no-install-recommends libmemcached-dev
RUN set -eux; apt-get update; apt-get upgrade -y; apt-get install -y --no-install-recommends libz-dev
RUN set -eux; apt-get update; apt-get upgrade -y; apt-get install -y --no-install-recommends libzip-dev
RUN set -eux; apt-get update; apt-get upgrade -y; apt-get install -y --no-install-recommends libpq-dev
RUN set -eux; apt-get update; apt-get upgrade -y; apt-get install -y --no-install-recommends libjpeg-dev
RUN set -eux; apt-get update; apt-get upgrade -y; apt-get install -y --no-install-recommends libpng-dev
RUN set -eux; apt-get update; apt-get upgrade -y; apt-get install -y --no-install-recommends libfreetype6-dev
RUN set -eux; apt-get update; apt-get upgrade -y; apt-get install -y --no-install-recommends libssl-dev
RUN set -eux; apt-get update; apt-get upgrade -y; apt-get install -y --no-install-recommends libwebp-dev
RUN set -eux; apt-get update; apt-get upgrade -y; apt-get install -y --no-install-recommends libmcrypt-dev
RUN set -eux; apt-get update; apt-get upgrade -y; apt-get install -y --no-install-recommends libonig-dev

# Installer les autres extensions PHP d'abord
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install zip
RUN docker-php-ext-install phar
RUN docker-php-ext-install curl

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY phpcs-custom /phpcs-custom

RUN composer self-update
RUN composer init -n --name="alma/php-cs" --description="php-cs" --type="library"

RUN composer config --no-interaction --no-plugins allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
RUN composer config --no-interaction --no-plugins allow-plugins.phpstan/extension-installer true
# PHPCompatibility 10.x is the first line to ship sniffs for PHP 8.2 -> 8.5; it is
# still an alpha, so allow it while keeping everything else on stable releases.
RUN composer config --no-interaction minimum-stability alpha
RUN composer config --no-interaction prefer-stable true
RUN composer require phpcsstandards/phpcsutils --no-interaction
RUN composer require phpcsstandards/phpcsextra --no-interaction
RUN composer require squizlabs/php_codesniffer:^3.13 --no-interaction
RUN composer require wp-coding-standards/wpcs --no-interaction
RUN composer require phpcompatibility/php-compatibility:10.0.0-alpha2 --no-interaction
RUN composer require phpstan/extension-installer:"*" --no-interaction
RUN composer require phpstan/phpstan:"*" --no-interaction
RUN composer require php-stubs/woocommerce-stubs:"*" --no-interaction
RUN composer require php-stubs/wordpress-stubs:"*" --no-interaction

RUN /composer/vendor/bin/phpcbf --config-set installed_paths /composer/vendor/phpcsstandards/phpcsutils,/composer/vendor/phpcsstandards/phpcsextra,/composer/vendor/squizlabs/php_codesniffer,/composer/vendor/wp-coding-standards/wpcs,/composer/vendor/phpcompatibility/php-compatibility,/phpcs-custom
RUN /composer/vendor/bin/phpcs --config-set installed_paths /composer/vendor/phpcsstandards/phpcsutils,/composer/vendor/phpcsstandards/phpcsextra,/composer/vendor/squizlabs/php_codesniffer,/composer/vendor/wp-coding-standards/wpcs,/composer/vendor/phpcompatibility/php-compatibility,/phpcs-custom

WORKDIR /app

ENTRYPOINT ["/composer/vendor/bin/phpcs"]
CMD ["--version"]