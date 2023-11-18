# FROM php:8.2-apache
FROM php:8.2

WORKDIR /var/www/html

RUN apt-get update && \
    apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install zip pdo_mysql

RUN a2enmod rewrite

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . .

RUN composer install

RUN chown -R www-data:www-data /var/www/html/storage

# CMD ["apache2-foreground"]
