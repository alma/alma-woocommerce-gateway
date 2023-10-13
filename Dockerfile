ARG COMPOSER_VERSION=2.2.18
ARG PHP_VERSION=7.4

FROM composer:${COMPOSER_VERSION} AS composer
FROM php:${PHP_VERSION}

ENV PHP_MEMORY_LIMIT=1024M


# Packages install
RUN export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y update \
    && apt-get install -y \
    libicu-dev \
    libpng-dev \
    libxml2-dev \
    libxslt-dev \
    libzip-dev \
    zlib1g-dev \
    mariadb-client \
    subversion \
    unzip \
    zip

RUN docker-php-ext-configure gd `cat /app/gd.config` \
    && docker-php-ext-install gd \
    && docker-php-ext-install intl \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install zip \
    && docker-php-ext-install soap \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install xsl \
    && docker-php-ext-install sockets


WORKDIR /app/woocommerce

# Composer install
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --link ./src/composer.json .

RUN composer install --prefer-dist --no-progress --no-suggest --dev

COPY --link ./src/ .

ARG WP_VERSION
ARG WC_VERSION

RUN bash /app/woocommerce/bin/install-wp-tests.sh ${WP_VERSION} ${WC_VERSION}

ENTRYPOINT ["./bin/entrypoint.sh"]
