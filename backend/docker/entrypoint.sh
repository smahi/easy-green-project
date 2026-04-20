#!/usr/bin/env bash
set -euo pipefail

# Only run Laravel bootstrap tasks when starting the HTTP server,
# not when the container is used for queue/scheduler (they pass their own command)
if [[ "${1:-}" != "php" ]]; then

    echo "==> Waiting for PostgreSQL..."
    until pg_isready -h "${DB_HOST}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" 2>/dev/null; do
        sleep 2
    done
    echo "    PostgreSQL is ready."

    echo "==> Running migrations..."
    php artisan migrate --force

    echo "==> Optimising..."
    php artisan filament:cache-components
    php artisan icons:cache
    php artisan optimize

    echo "==> Linking storage..."
    php artisan storage:link --quiet || true

    echo "==> Fixing permissions..."
    chown -R www-data:www-data storage bootstrap/cache

fi

# If no command was passed, start FrankenPHP
if [[ $# -eq 0 ]]; then
    echo "==> Starting FrankenPHP..."
    exec frankenphp run --config /etc/caddy/Caddyfile
fi

echo "==> Starting: $*"
exec "$@"
