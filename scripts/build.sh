#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BACKEND_DIR="$PROJECT_ROOT/backend"
BUILD_DIR="/tmp/laravel-build"
ARTIFACT="$PROJECT_ROOT/release.tar.gz"

rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

echo "==> Sync source"
rsync -a \
  --exclude=".git" \
  --exclude="node_modules" \
  --exclude="tests" \
  --exclude=".env" \
  --exclude="storage" \
  --exclude="bootstrap/cache" \
  "$BACKEND_DIR/" "$BUILD_DIR/"

cd "$BUILD_DIR"

echo "==> Ensure Laravel runtime dirs"
mkdir -p bootstrap/cache storage/framework/{cache,sessions,views}

chmod -R 775 bootstrap/cache storage

echo "==> Install production dependencies (NO DEV)"
composer install \
  --no-dev \
  --prefer-dist \
  --optimize-autoloader \
  --classmap-authoritative \
  --no-interaction

echo "==> Validate Laravel autoload"

php artisan optimize:clear
composer dump-autoload -o

php -r "require 'vendor/autoload.php'; echo 'autoload OK';"


echo "==> Build frontend"
if [ -f package.json ]; then
  npm ci
  npm run build
fi

echo "==> Hard validation (IMPORTANT)"
test -f vendor/autoload.php

echo "==> Create artifact (clean)"
tar --exclude="./node_modules" \
    --exclude="./tests" \
    --exclude="./.git" \
    -czf "$ARTIFACT" .

echo "DONE -> $ARTIFACT"
