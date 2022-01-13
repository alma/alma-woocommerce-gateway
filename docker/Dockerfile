FROM wordpress:php7.3-fpm

# install composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# install wp-cli
RUN curl https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp
RUN chmod +x /usr/local/bin/wp
RUN echo "alias wp='wp --allow-root'" >> /root/.bashrc

# install vim & less
RUN apt-get update && apt-get install -y vim less unzip mariadb-client-10.5

COPY --chown=www-data:www-data ./wp-config-docker.php /usr/src/wordpress/
COPY ./docker-entrypoint.sh ./configure-wc.sh ./env.*local /usr/local/bin
RUN chmod +x /usr/local/bin/*sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
