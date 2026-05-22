FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql bcmath mbstring exif pcntl zip \
    && pecl install redis pcov \
    && docker-php-ext-enable redis pcov \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/99-custom.ini

RUN mkdir -p /var/log/php-fpm && chown www-data:www-data /var/log/php-fpm

EXPOSE 9000

CMD ["php-fpm"]
