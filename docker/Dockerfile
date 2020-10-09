FROM wordpress:latest

# install composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# install xdebug
RUN pecl install xdebug
