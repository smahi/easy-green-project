FROM php:8.5-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git unzip curl zip \
    libpng-dev libonig-dev libxml2-dev \
    libzip-dev libicu-dev \
    libpq-dev

# PHP extensions
RUN docker-php-ext-install \
    pdo pdo_pgsql mbstring exif pcntl bcmath gd intl zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chmod -R 775 storage bootstrap/cache

CMD ["php-fpm"]
