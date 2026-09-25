ARG PHP_VERSION=latest

FROM composer:2 AS composer
FROM php:${PHP_VERSION}

ENV PHP_MEMORY_LIMIT=1024M
ENV DEBIAN_FRONTEND=noninteractive

# Bullseye security updates left deb.debian.org when LTS ended (2026-08-31) and are not on
# archive.debian.org yet, so pin them to the last snapshot taken before the EOL.
RUN . /etc/os-release && if [ "$VERSION_CODENAME" = bullseye ]; then \
      sed -i 's|http://deb.debian.org/debian-security|http://snapshot.debian.org/archive/debian-security/20260801T000000Z|' /etc/apt/sources.list && \
      echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99-bullseye-eol; \
    fi

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

COPY --chown=phpuser ./composer.json ./
COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY --chown=phpuser ./ ./
RUN composer install --prefer-dist --no-progress

ARG WP_VERSION
ARG WC_VERSION

RUN bash ./bin/install-wp-tests.sh ${WP_VERSION} ${WC_VERSION}

ENTRYPOINT ["./bin/entrypoint.sh"]
CMD [ "php", "-S", "0.0.0.0:8000"]
