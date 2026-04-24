#!/usr/bin/env bash
# ── deploy.sh ──────────────────────────────────────────────────────────────
# Usage:
#   ./deploy.sh status         – show pod and container status
#   ./deploy.sh artisan <cmd>  – run artisan inside the app container
#   ./deploy.sh logs [name]    – tail pod or container logs
#   ./deploy.sh shell          – shell inside the app container
#   ./deploy.sh down           – stop the pod
#   ./deploy.sh start          – start the pod
#   ./deploy.sh backup         – pg_dump → ./backups/

set -euo pipefail

POD="easygreen-pod"
APP_CONTAINER="easygreen-app"
DB_CONTAINER="easygreen-db"

info()  { echo -e "\033[32m==>\033[0m $*"; }
error() { echo -e "\033[31mERROR:\033[0m $*" >&2; exit 1; }

require_env() {
    [[ -f .env ]] || error ".env not found."
}

cmd_status() {
    podman pod ps
    echo
    podman ps --filter "pod=${POD}"
}

cmd_artisan() {
    require_env
    podman exec -it "$APP_CONTAINER" php artisan "$@"
}

cmd_logs() {
    local name="${1:-$POD}"
    if [[ "$name" == "$POD" ]]; then
        podman pod logs -f "$POD"
    else
        podman logs -f "$name"
    fi
}

cmd_shell() {
    require_env
    podman exec -it "$APP_CONTAINER" bash
}

cmd_down() {
    info "Stopping pod..."
    podman pod stop "$POD"
}

cmd_start() {
    info "Starting pod..."
    podman pod start "$POD"
}

cmd_backup() {
    require_env
    source .env
    mkdir -p backups
    local filename="backups/pg_$(date +%Y%m%d_%H%M%S).sql.gz"
    info "Dumping database to $filename..."
    podman exec -i "$DB_CONTAINER" pg_dump -U "$DB_USERNAME" "$DB_DATABASE" | gzip > "$filename"
    info "Backup saved: $filename"
}

case "${1:-status}" in
    status)   cmd_status ;;
    artisan)  shift; cmd_artisan "$@" ;;
    logs)     shift; cmd_logs "${1:-}" ;;
    shell)    cmd_shell ;;
    down)     cmd_down ;;
    start)    cmd_start ;;
    backup)   cmd_backup ;;
    *)        error "Unknown command: $1" ;;
esac
