# EASY GREEN - Ansible Operations

Single-source guide for provisioning, deploying, backing up, and restoring the
Easy Green backend with Docker Compose.

## Playbooks

```text
ansible/
├── harden.yml              # One-time host hardening
├── bootstrap-docker.yml   # One-time Docker bootstrap
├── deploy-docker.yml    # Main app deploy playbook
├── backup-download.yml # Download a sanitized backend backup
├── restore-backup.yml  # Restore a selected backend backup
├── rollback.yml       # Legacy release/symlink rollback helper
├── beszel-vm.yml          # Beszel VM host monitoring
├── beszel-containers.yml   # Beszel Docker container monitoring
├── uptime-kuma.yml       # Uptime Kuma service monitoring
├── inventory/
│   ├── staging.ini
│   └── production.ini
├── group_vars/
├── templates/
│   └── docker-compose.yml.j2  # Docker Compose template
├── vault.yml
└── README.md
```

## Prerequisites

```bash
sudo apt install ansible python3-argcomplete
ansible-galaxy collection install community.general ansible.posix
```

Optional vault password file:

```bash
echo "your_vault_password" > ~/.ansible_vault_pass
chmod 600 ~/.ansible_vault_pass
```

## Setup

1. Edit `ansible/vault.yml` with real secrets.
2. Verify the target inventory.
3. Generate an app key locally and store it in the vault.

```bash
ansible-vault edit ansible/vault.yml
cd backend && php artisan key:generate --show
```

## Run Order

Fresh server flow:

```text
harden.yml -> reboot -> bootstrap-docker.yml -> deploy-docker.yml
```

Staging:

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/harden.yml
ansible staging -i ansible/inventory/staging.ini -m reboot
ansible-playbook -i ansible/inventory/staging.ini ansible/bootstrap-docker.yml
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-docker.yml
```

Production:

```bash
ansible-playbook -i ansible/inventory/production.ini ansible/harden.yml
ansible production -i ansible/inventory/production.ini -m reboot
ansible-playbook -i ansible/inventory/production.ini ansible/bootstrap-docker.yml
ansible-playbook -i ansible/inventory/production.ini ansible/deploy-docker.yml
```

## Deployment Tags

```bash
# Build image locally, sync to VPS, load, and deploy
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-docker.yml

# Sync only (skip build)
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-docker.yml --tags deploy

# Build locally only
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-docker.yml --tags build
```

## Monitoring

### Uptime Kuma (Service Monitoring)

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/uptime-kuma.yml
```

Then open http://<VM-IP>:3001 and configure monitors.

### Beszel (Host + Container Monitoring)

```bash
# VM host metrics (binary agent)
ansible-playbook -i ansible/inventory/staging.ini ansible/beszel-vm.yml \
  -e beszel_key="..." -e beszel_token="..."

# Docker container metrics (read-only socket)
ansible-playbook -i ansible/inventory/staging.ini ansible/beszel-containers.yml \
  -e beszel_key="..." -e beszel_token="..."
```

See [Beszel Guide](#beszel-vmyml) below for setup.

## Security Features

All containers follow security best practices:

| Feature | Implementation |
|---------|-------------|
| Non-root user | `user: "1000:1000"` |
| Read-only filesystem | `read_only: true` |
| Drop capabilities | `cap_drop: ALL` |
| No privilege escalation | `no-new-privileges: true` |
| Temporary filesystem | `tmpfs: /tmp` |
| Resource limits | CPU + memory limits |
| Health checks | service_healthy conditions |
| Internal network | `backend` network |

## What Each Playbook Does

### `harden.yml`

Run once on a new host.

- updates packages
- hardens SSH
- configures UFW and Fail2ban
- applies sysctl settings
- hardens `/tmp` and `/dev/shm`
- enables unattended upgrades, auditd, and other baseline protections

### `bootstrap-docker.yml`

Run once after hardening.

- installs Docker Engine 28.x
- adds deploy user to docker group
- configures Docker daemon with security settings (`/etc/docker/daemon.json`)
- enables unprivileged port binding (port 80)
- creates project directories and data folders
- enables and starts Docker service

### `deploy-docker.yml`

Run on every deploy.

- syncs `backend/` to the host
- copies `docker-compose.yml` template
- preserves the existing remote `.env`
- builds app image locally
- transfers image tarball to VPS
- loads image and starts containers with Docker Compose
- smoke-tests the deployed app

Useful tags:

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-docker.yml --tags build
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-docker.yml --tags deploy
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-docker.yml --tags smoke
```

### `backup-download.yml`

Downloads a sanitized backend archive from the host:

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/backup-download.yml
```

### `restore-backup.yml`

Restores a downloaded backend archive and rebuilds the Docker Compose stack:

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/restore-backup.yml \
  -e restore_backup_file=ansible/backups/.../easy-green-backend-...tar.gz
```

## Runtime Layout

The deployed stack runs as Docker Compose services:

- `easygreen-db` - PostgreSQL 18
- `easygreen-valkey` - Valkey 8
- `easygreen-app` - Laravel app (FrankenPHP)
- `easygreen-queue` - Queue worker
- `easygreen-scheduler` - Scheduler
- `easygreen-caddy` - Caddy webserver

Services communicate over internal `backend` network.
Only Caddy publishes host ports 80/443.

## Common Operations

Connectivity check:

```bash
ansible staging -i ansible/inventory/staging.ini -m ping
```

Remote artisan command:

```bash
ansible staging -i ansible/inventory/staging.ini -m command \
  -a "docker exec easygreen-app-1 php artisan migrate:status" \
  --become=false
```

Tail app logs:

```bash
ansible staging -i ansible/inventory/staging.ini -m command \
  -a "docker logs --tail=50 easygreen-app-1" \
  --become=false
```

Container status:

```bash
ansible staging -i ansible/inventory/staging.ini -m command \
  -a "docker ps" \
  --become=false
```

## Troubleshooting

Missing collections:

```bash
ansible-galaxy collection install community.general ansible.posix
```

`rsync` missing on target:

```bash
ansible staging -i ansible/inventory/staging.ini -m apt \
  -a "name=rsync state=present" --become
```

Docker not running:

```bash
ansible staging -i ansible/inventory/staging.ini -m systemd \
  -a "name=docker state=started" --become
```

## Beszel VM/Container Monitoring

This project uses **Beszel** for monitoring with a security-first approach.

### Security Principles

| Layer | Approach | Why |
|-------|----------|-----|
| Hub | Docker container | Easy to manage, rebuildable |
| VM Agent | Binary (not container) | No container escape risk |
| Container Agent | Container + :ro socket | Read-only, minimal risk |
| User | deploy_user (not root) | No privilege escalation |

### What's Monitored

- **VM host**: CPU, RAM, disk, network, uptime
- **Docker containers**: Container status, CPU, RAM (via read-only socket)

### What's NOT Monitored

- Container logs (use Dozzle or similar separately)

See: https://beszel.dev/guide/getting-started