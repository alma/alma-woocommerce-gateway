FROM composer:2.2 AS composer
FROM php:5.6-fpm

ARG UID

#ENV PHP_MEMORY_LIMIT=1024M
ENV DEBIAN_FRONTEND=noninteractive

# For PHP 5.6 image, update sources.list to outdated Debian archives
 RUN sed -i s/deb.debian.org/archive.debian.org/g /etc/apt/sources.list && \
     sed -i s/security.debian.org/archive.debian.org/g /etc/apt/sources.list && \
     sed -i s/stretch-updates/stretch/g /etc/apt/sources.list
 
# Install dependencies
RUN apt update \
    && apt install -y --no-install-recommends \
    git \
    rsync \
    unzip \
    zip \
    && \
    apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Create non-root user
RUN useradd -u ${UID} -ms /bin/bash phpuser
WORKDIR /home/phpuser
USER phpuser

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --chown=phpuser ./composer.json ./composer.json
RUN composer install --prefer-dist --no-progress --working-dir=./
