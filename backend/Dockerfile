FROM dunglas/frankenphp:1.3.0-php8.5-alpine

RUN addgroup -S app && adduser -S app -G app -u 1000

WORKDIR /var/www

RUN apk update && apk add --no-cache \
    git unzip curl zip \
    libpng-dev libxml2-dev \
    libzip-dev icu-dev postgresql-dev oniguruma-dev

RUN docker-php-ext-install \
    pdo pdo_pgsql mbstring exif pcntl bcmath gd intl zip

COPY --from=composer:2.8.0 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chmod -R 775 storage bootstrap/cache

USER app

CMD ["frankenphp", "server"]
