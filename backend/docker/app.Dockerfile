# ── Stage 1: PHP dependencies (Composer) ─────────────────────────────────────
FROM composer:2 AS composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs
#   ^^^^^^^^^^^^^^^^^^^^^^^^^^
#   The composer image has a minimal PHP. We install extensions in the
#   FrankenPHP stage below — ignore missing ext checks here.

# ── Stage 2: Node / frontend assets ──────────────────────────────────────────
FROM node:22-alpine AS node

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci

COPY . .
COPY --from=composer /app/vendor ./vendor
RUN npm run build

# ── Stage 3: Final image (FrankenPHP + PHP 8.4, upgrade to 8.5 when released) ─
FROM dunglas/frankenphp:php8.4-alpine AS app

LABEL org.opencontainers.image.description="Laravel 12 / Filament 5 app"

# System libraries required by PHP extensions
RUN apk add --no-cache \
    bash \
    git \
    postgresql-client \
    icu-libs \
    libzip \
    imagemagick \
    freetype \
    libjpeg-turbo \
    libpng \
    libwebp \
    libavif \
    oniguruma

# PHP extensions
# FrankenPHP already bundles: opcache, sodium, curl, mbstring, xml, dom,
# ctype, fileinfo, filter, hash, json, openssl, pcre, pdo, session,
# tokenizer, zip
RUN install-php-extensions \
    bcmath \
    exif \
    gd \
    imagick \
    intl \
    pcntl \
    pdo_pgsql \
    pgsql \
    redis \
    zip

# PHP production configuration
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

# Copy application source
COPY --chown=www-data:www-data . .

# Vendor from composer stage
COPY --from=composer --chown=www-data:www-data /app/vendor ./vendor

# Built frontend assets from node stage
COPY --from=node --chown=www-data:www-data /app/public/build ./public/build
COPY --from=node --chown=www-data:www-data /app/public/css ./public/css
COPY --from=node --chown=www-data:www-data /app/public/fonts ./public/fonts
COPY --from=node --chown=www-data:www-data /app/public/js ./public/js

# FrankenPHP inner Caddyfile
COPY docker/Caddyfile /etc/caddy/Caddyfile

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]

