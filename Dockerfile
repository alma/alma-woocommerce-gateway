ARG PHP_VERSION
ARG COMPOSER_VERSION

FROM composer:${COMPOSER_VERSION} as composer
FROM php:${PHP_VERSION}-fpm
ARG PHP_VERSION

ENV DEBIAN_FRONTEND noninteractive

# Debian 11 (bullseye) reached end of LTS in August 2026: its security suite still
# publishes an index on deb.debian.org but the matching .deb files are gone from the
# pool, so any apt install resolving to a *-security version fails with a 404.
# Drop that suite and stay on the regular bullseye archive, which is still served.
RUN if grep -q bullseye /etc/apt/sources.list 2>/dev/null; then \
        sed -i '/debian-security/d' /etc/apt/sources.list; \
    fi

# Install dependencies
RUN apt update && \
    apt install -y --no-install-recommends \
    git \
    rsync \
    zip \
    unzip \
    libicu-dev \
    pkg-config \
    curl \
    && \
    # Add Node.js repository and install Node.js 20.x and npm
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN case ${PHP_VERSION} in \
        8.0|8.1|8.2|8.3 ) pecl install xdebug-3.3.2 && docker-php-ext-enable xdebug;; \
        *) pecl install xdebug-2.9.8 && docker-php-ext-enable xdebug;; \
    esac

# Install PHP extensions
RUN docker-php-ext-install intl

RUN usermod -u 1000 www-data
RUN groupmod -g 1000 www-data

RUN mkdir -p /app/vendor && chown -R www-data:www-data /app

USER www-data
WORKDIR /app

COPY --link .docker/php.ini /usr/local/etc/php/php.ini

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --link composer.json ./
RUN composer install --prefer-dist --no-progress --no-cache
