ARG PHP_VERSION=latest

FROM composer:2 AS composer
FROM php:${PHP_VERSION}

ENV PHP_MEMORY_LIMIT=1024M
ENV DEBIAN_FRONTEND=noninteractive

# Install dependencies
RUN apt update && \
    apt install -y --no-install-recommends \
    libicu-dev \
    libpng-dev \
    libxml2-dev \
    libxslt-dev \
    libzip-dev \
    mariadb-client \
    subversion \
    unzip \
    zip \
    zlib1g-dev \
    && \
    # Cleanup APT
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* && \
    # Install PHP extensions
    docker-php-ext-install -j$(nproc) \
    bcmath \
    gd \
    intl \
    mysqli \
    pdo_mysql \
    soap \
    sockets \
    xsl \
    zip

RUN pecl install xdebug-3.1.3 \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Create non-root user
RUN useradd -ms /bin/bash phpuser
WORKDIR /home/phpuser
USER phpuser

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --chown=phpuser ./src/composer.json ./
RUN composer install --prefer-dist --no-progress

COPY --chown=phpuser ./src/ ./

ARG WP_VERSION
ARG WC_VERSION

RUN bash ./bin/install-wp-tests.sh ${WP_VERSION} ${WC_VERSION}

ENTRYPOINT ["./bin/entrypoint.sh"]
CMD [ "php", "-S", "0.0.0.0:8000"]
