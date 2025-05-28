ARG PHP_IMG_TAG=7.4-fpm

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
RUN composer self-update
RUN composer init -n --name="alma/php-cs" --description="php-cs" --type="library"

RUN composer config --no-interaction --no-plugins allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
RUN composer require phpcsstandards/phpcsutils --no-interaction
RUN composer require phpcsstandards/phpcsextra --no-interaction
RUN composer require squizlabs/php_codesniffer --no-interaction
RUN composer require wp-coding-standards/wpcs --no-interaction
RUN composer require phpcompatibility/php-compatibility --no-interaction
RUN composer require phpcompatibility/phpcompatibility-wp:"*" --no-interaction
RUN composer require phpcompatibility/phpcompatibility-paragonie:"*" --no-interaction

RUN /composer/vendor/bin/phpcbf --config-set installed_paths /composer/vendor/phpcsstandards/phpcsutils,/composer/vendor/phpcsstandards/phpcsextra,/composer/vendor/squizlabs/php_codesniffer,/composer/vendor/wp-coding-standards/wpcs,/composer/vendor/phpcompatibility/php-compatibility,/composer/vendor/phpcompatibility/phpcompatibility-wp,/composer/vendor/phpcompatibility/phpcompatibility-paragonie
RUN /composer/vendor/bin/phpcs --config-set installed_paths /composer/vendor/phpcsstandards/phpcsutils,/composer/vendor/phpcsstandards/phpcsextra,/composer/vendor/squizlabs/php_codesniffer,/composer/vendor/wp-coding-standards/wpcs,/composer/vendor/phpcompatibility/php-compatibility,/composer/vendor/phpcompatibility/phpcompatibility-wp,/composer/vendor/phpcompatibility/phpcompatibility-paragonie

WORKDIR /app

ENTRYPOINT ["/composer/vendor/bin/phpcs"]
CMD ["--version"]