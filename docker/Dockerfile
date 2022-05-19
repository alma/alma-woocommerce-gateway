FROM wordpress:php7.3-fpm

# install composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# install wp-cli
RUN curl https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp
RUN chmod +x /usr/local/bin/wp
RUN echo "alias wp='wp --allow-root'" >> /root/.bashrc

# install vim & less
RUN apt-get update && apt-get install -y vim less unzip mariadb-client-10.5 && apt-get remove -y libaom0 && apt-get -y autoremove
RUN rm /usr/local/etc/php/conf.d/docker-php-ext-imagick.ini

COPY --chown=www-data:www-data ./wp-config-docker.php /usr/src/wordpress/
COPY ./docker-entrypoint.sh ./configure-wc.sh ./env.*local /usr/local/bin/
RUN chmod +x /usr/local/bin/*sh

# copy the sample products xml file.
RUN chmod +w /var/www/html/
ADD --chown=www-data:www-data ./sample_products_for_alma_e2e_tests.xml /var/www/html/

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]

