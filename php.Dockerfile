# Stage 1: Composer Container
FROM composer:2 AS composer_stage

# Set the working directory inside the Composer container
WORKDIR /app

# Copy the composer.json and composer.lock to the container
COPY ./composer.json composer.json
COPY ./composer.lock composer.lock

# Install Composer dependencies
RUN composer install --ignore-platform-reqs --prefer-dist --no-scripts --no-progress --no-interaction --no-dev --no-autoloader

# Stage 2: PHP 8.2 Apache Container
FROM php:8.2-apache

RUN a2enmod rewrite

# Copy the vendor folder from the Composer container to /var/www/html
COPY --from=composer_stage /app/vendor /var/www/html/vendor

COPY --from=composer_stage /usr/bin/composer /usr/local/bin/composer

COPY ./src /var/www/html/src
COPY ./test /var/www/html/test
COPY .htaccess /var/www/html

COPY composer.json /var/www/html
COPY composer.lock /var/www/html

RUN composer dump-autoload --optimize