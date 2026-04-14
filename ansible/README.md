# EASY GREEN — Ansible Deployment

## Stack

| Component | Choice |
|-----------|--------|
| Reverse Proxy | **Caddy** (auto TLS via Let's Encrypt) |
| PHP Runtime | **PHP 8.5-FPM** (Laravel + Filament 5 extensions) |
| Framework | **Laravel 12 + Filament 5** |
| Database | **PostgreSQL 18** |
| Cache / Queue | **Valkey** (Redis-compatible, drop-in) |
| Queue Workers | **Supervisor** |
| Asset Build | **Node.js 22 + Vite** |

## Directory Structure

```
ansible/
├── deploy.yml              # Main playbook
├── inventory/
│   ├── staging.ini         # u24 multipass VM
│   └── production.ini      # VPS
├── group_vars/
│   ├── all.yml             # Shared variables
│   ├── staging.yml         # Staging overrides
│   └── production.yml      # Production overrides
├── roles/
│   ├── system/             # Hardening, firewall, fail2ban, deploy user
│   ├── postgresql/         # PG18 install + DB + user
│   ├── valkey/             # Valkey install + password config
│   ├── php/                # PHP-FPM 8.4 + extensions + pool
│   ├── caddy/              # Caddy install + Caddyfile (auto TLS)
│   ├── node/               # Node.js 22 + npm install + vite build
│   ├── laravel/            # Git checkout, .env, migrate, permissions
│   ├── supervisor/         # Queue workers (artisan queue:work)
│   └── backups/            # pg_dump cron + rotation
├── vault.yml               # Encrypted secrets (DB pass, SMTP, etc.)
└── README.md               # This file
```

## Prerequisites

```bash
sudo apt install ansible python3-argcomplete
```

## Setup

### 1. Configure Vault Secrets

```bash
# Edit vault.yml with your real secrets
vim ansible/vault.yml

# Encrypt it with ansible-vault
ansible-vault encrypt ansible/vault.yml
# You'll be prompted for a vault password
```

Or create a vault password file:

```bash
echo "your_vault_password" > ~/.ansible_vault_pass
chmod 600 ~/.ansible_vault_pass
```

### 2. Edit Inventory

```bash
# Staging is pre-configured for the u24 multipass VM
cat ansible/inventory/staging.ini

# For production, update production.ini with your real details
vim ansible/inventory/production.ini
```

### 3. Update Vault Secrets

```bash
# Generate an APP_KEY locally
cd backend && php artisan key:generate --show

# Edit the vault to set real values
ansible-vault edit ansible/vault.yml
```

## Usage

### Full deployment (infra + app)

```bash
# Staging
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy.yml --ask-vault-pass

# Production
ansible-playbook -i ansible/inventory/production.ini ansible/deploy.yml --ask-vault-pass
```

### Infra only (first time setup)

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy.yml --ask-vault-pass --tags "infra"
```

### App deploy only (code updates, after infra is set up)

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy.yml --ask-vault-pass --tags "deploy"
```

### Dry run (check mode)

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy.yml --ask-vault-pass --check --diff
```

## Deployment Flow (Capistrano-style)

```
/var/www/easy-green/
├── releases/
│   ├── 20260414T120000/    ← current release (symlink target)
│   ├── 20260414T115500/    ← previous release
│   └── ...
├── shared/
│   ├── storage/            ← persistent storage (linked)
│   └── bootstrap/cache/    ← persistent cache (linked)
└── current -> releases/20260414T120000/   ← active release
```

Old releases beyond the 5 most recent are automatically cleaned up.

## Restore Snapshot (Staging)

If you need a clean VM to re-test the playbook:

```bash
multipass restore u24 --name <snapshot_name>
```

## Variables Reference

### `group_vars/all.yml` (shared)

| Variable | Description |
|----------|-------------|
| `app_repo` | Git URL for the Laravel backend |
| `app_branch` | Branch to deploy |
| `app_deploy_user` | System user for deployment |
| `app_url_domain` | Domain name for Caddy TLS |
| `db_name` / `db_user` / `db_password` | PostgreSQL credentials |
| `valkey_host` / `valkey_password` | Valkey credentials |
| `php_version` | PHP version (default 8.4) |
| `queue_workers` | Number of queue worker processes |
| `backup_retention_days` | How many days to keep backups |

### Environment-specific (`staging.yml` / `production.yml`)

| Variable | Staging | Production |
|----------|---------|------------|
| `app_debug` | `true` | `false` |
| `caddy_tls_mode` | `internal` (self-signed) | `letsencrypt` |
| `backups_enabled` | `false` | `true` |
