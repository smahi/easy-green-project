#!/usr/bin/env bash
# ── deploy.sh ──────────────────────────────────────────────────────────────
# Usage:
#   ./deploy.sh            – build and (re)start everything
#   ./deploy.sh pull       – git pull + rebuild + restart
#   ./deploy.sh artisan <cmd>  – run artisan inside the app container
#   ./deploy.sh logs [svc] – tail logs
#   ./deploy.sh shell      – bash inside the app container
#   ./deploy.sh down       – stop (data volumes preserved)
#   ./deploy.sh backup     – pg_dump → ./backups/

set -euo pipefail

COMPOSE="podman-compose"
APP_SERVICE="app"

info()  { echo -e "\033[32m==>\033[0m $*"; }
error() { echo -e "\033[31mERROR:\033[0m $*" >&2; exit 1; }

require_env() {
    [[ -f .env ]] || error ".env not found. Copy .env.production → .env and fill in secrets."
}

cmd_deploy() {
    require_env

    # Build the app image first and tag it explicitly.
    # queue + scheduler reuse this same image — no separate builds needed.
    info "Building app image..."
    podman build \
        -f docker/app.Dockerfile \
        -t myapp_app:latest \
        .

    info "Starting services..."
    $COMPOSE up -d --remove-orphans

    info "Done. Run './deploy.sh logs' to watch."
}

cmd_pull() {
    info "Pulling latest code..."
    git pull
    cmd_deploy
}

cmd_artisan() {
    require_env
    $COMPOSE exec "$APP_SERVICE" php artisan "$@"
}

cmd_logs() {
    local svc="${1:-}"
    if [[ -n "$svc" ]]; then
        $COMPOSE logs -f "$svc"
    else
        $COMPOSE logs -f
    fi
}

cmd_shell() {
    require_env
    $COMPOSE exec "$APP_SERVICE" bash
}

cmd_down() {
    info "Stopping containers (volumes preserved)..."
    $COMPOSE down
}

cmd_backup() {
    require_env
    # shellcheck disable=SC1091
    source .env
    mkdir -p backups
    local filename="backups/pg_$(date +%Y%m%d_%H%M%S).sql.gz"
    info "Dumping database to $filename..."
    $COMPOSE exec -T db pg_dump -U "$DB_USERNAME" "$DB_DATABASE" | gzip > "$filename"
    info "Backup saved: $filename"
}

case "${1:-deploy}" in
    deploy)   cmd_deploy ;;
    pull)     cmd_pull ;;
    artisan)  shift; cmd_artisan "$@" ;;
    logs)     shift; cmd_logs "${1:-}" ;;
    shell)    cmd_shell ;;
    down)     cmd_down ;;
    backup)   cmd_backup ;;
    *)        error "Unknown command: $1" ;;
esac
