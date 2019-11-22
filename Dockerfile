FROM wesleyelfring/laravel

RUN curl -o /usr/local/bin/composer https://getcomposer.org/download/1.9.1/composer.phar
RUN chmod 755 /usr/local/bin/composer
RUN docker-php-ext-enable xdebug