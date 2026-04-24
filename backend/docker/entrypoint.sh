#!/usr/bin/env bash
set -euo pipefail

wait_for_postgres() {
    echo "==> Waiting for PostgreSQL..."
    until pg_isready -h "${DB_HOST}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" 2>/dev/null; do
        sleep 2
    done
    echo "    PostgreSQL is ready."
}

wait_for_valkey() {
    if [[ -z "${REDIS_HOST:-}" || -z "${REDIS_PORT:-}" ]]; then
        return 0
    fi

    echo "==> Waiting for Valkey..."
    until php -r '
        $redis = new Redis();
        $redis->connect(getenv("REDIS_HOST"), (int) getenv("REDIS_PORT"), 2.5);
        $password = getenv("REDIS_PASSWORD");
        if ($password !== false && $password !== "") {
            $redis->auth($password);
        }
        exit($redis->ping() ? 0 : 1);
    ' >/dev/null 2>&1; do
        sleep 2
    done
    echo "    Valkey is ready."
}

run_web_bootstrap() {
    wait_for_postgres
    wait_for_valkey

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
}

run_worker_bootstrap() {
    wait_for_postgres
    wait_for_valkey
}

# Only run the full Laravel bootstrap for the web container.
# Queue and scheduler containers still wait for dependencies, but they do not
# run migrations or optimization tasks on every start.
if [[ $# -eq 0 ]]; then
    run_web_bootstrap
elif [[ "${1:-}" == "php" && "${2:-}" == "artisan" ]]; then
    case "${3:-}" in
        queue:work|schedule:work)
            run_worker_bootstrap
            ;;
    esac
fi

# If no command was passed, start FrankenPHP
if [[ $# -eq 0 ]]; then
    echo "==> Starting FrankenPHP..."
    exec frankenphp run --config /etc/caddy/Caddyfile
fi

echo "==> Starting: $*"
exec "$@"
