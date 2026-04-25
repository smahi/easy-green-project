# EASY GREEN - Ansible Operations

Single-source guide for provisioning, deploying, backing up, and restoring the
Easy Green backend with a rootless Podman pod.

## Playbooks

```text
ansible/
├── harden.yml            # One-time host hardening
├── bootstrap-pod.yml     # One-time Podman/rootless bootstrap
├── deploy-pod.yml        # Main app deploy playbook
├── backup-download.yml   # Download a sanitized backend backup
├── restore-backup.yml    # Restore a selected backend backup
├── rollback.yml          # Legacy release/symlink rollback helper
├── beszel-vm.yml        # Beszel monitoring (hub + agent)
├── inventory/
│   ├── staging.ini
│   └── production.ini
├── group_vars/
├── tasks/
│   └── pod_stack.yml     # Shared pod recreation logic
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
harden.yml -> reboot -> bootstrap-pod.yml -> deploy-pod.yml
```

Staging:

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/harden.yml
ansible staging -i ansible/inventory/staging.ini -m reboot
ansible-playbook -i ansible/inventory/staging.ini ansible/bootstrap-pod.yml
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-pod.yml
```

Production:

```bash
ansible-playbook -i ansible/inventory/production.ini ansible/harden.yml
ansible production -i ansible/inventory/production.ini -m reboot
ansible-playbook -i ansible/inventory/production.ini ansible/bootstrap-pod.yml
ansible-playbook -i ansible/inventory/production.ini ansible/deploy-pod.yml
```

## Monitoring

Beszel for VM host monitoring:

```bash
# With key/token from Beszel UI (Settings → Tokens)
ansible-playbook -i ansible/inventory/staging.ini ansible/beszel-vm.yml \
  -e beszel_key="ssh-ed25519 AAA..." -e beszel_token="xxx..."

ansible-playbook -i ansible/inventory/production.ini ansible/beszel-vm.yml \
  -e beszel_key="ssh-ed25519 AAA..." -e beszel_token="xxx..."
```

See [Beszel Guide](#beszel-vmyml) below for setup.

## Monitoring Recommendations

This project uses **Beszel** for VM host monitoring with a security-first approach.

### Security Principles

| Layer | Approach | Why |
|-------|----------|-----|
| Hub | Podman container | Easy to manage, rebuildable |
| Agent | Binary (not container) | No container escape risk |
| User | deploy_user (not root) | No privilege escalation |
| Socket | None | VM metrics via system APIs only |

### What's Monitored

- Host CPU, RAM, disk usage, network I/O
- System uptime, load average

### What's NOT Monitored

- Podman containers - requires socket (security tradeoff)
- Container monitoring in separate playbook

- Podman containers - requires socket access (security trade-off)
- Container monitoring in separate playbook

See: https://beszel.dev/guide/getting-started

## What Each Playbook Does

### `harden.yml`

Run once on a new host.

- updates packages
- hardens SSH
- configures UFW and Fail2ban
- applies sysctl settings
- hardens `/tmp` and `/dev/shm`
- enables unattended upgrades, auditd, and other baseline protections

### `bootstrap-pod.yml`

Run once after hardening.

- installs Podman, `passt`/`pasta`, `slirp4netns`, `uidmap`, and `rsync`
- enables rootless low-port binding
- sets rootless Podman to prefer `pasta`
- configures registry search order and container DNS
- enables the user Podman socket and linger

### `deploy-pod.yml`

Run on every deploy.

- syncs `backend/` to the host
- preserves the existing remote `.env`
- forces Laravel DB/Redis hosts to `127.0.0.1` inside the pod
- builds the app image
- recreates the rootless Podman pod with `pasta`
- recreates DB, Valkey, app, queue, scheduler, and Caddy containers
- enables the user systemd unit for pod restart on boot
- smoke-tests the deployed app

Useful tags:

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-pod.yml --tags build
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-pod.yml --tags deploy
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-pod.yml --tags smoke
```

### `backup-download.yml`

Downloads a sanitized backend archive from the host:

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/backup-download.yml
```

### `restore-backup.yml`

Restores a downloaded backend archive and then rebuilds the same Podman pod stack:

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/restore-backup.yml \
  -e restore_backup_file=ansible/backups/u24/easy-green-backend-u24-20260424T051901.tar.gz
```

## Runtime Layout

The deployed stack runs as one rootless Podman pod with `pasta` networking:

- `easygreen-db`
- `easygreen-valkey`
- `easygreen-app`
- `easygreen-queue`
- `easygreen-scheduler`
- `easygreen-caddy`

Containers communicate over `127.0.0.1` inside the shared pod network
namespace. Only Caddy publishes host ports.

## Common Operations

Connectivity check:

```bash
ansible staging -i ansible/inventory/staging.ini -m ping
```

Remote artisan command:

```bash
ansible staging -i ansible/inventory/staging.ini -m command \
  -a "podman exec easygreen-app php artisan migrate:status" \
  --become=false
```

Tail app logs:

```bash
ansible staging -i ansible/inventory/staging.ini -m command \
  -a "podman logs --tail=50 easygreen-app" \
  --become=false
```

Pod status:

```bash
ansible staging -i ansible/inventory/staging.ini -m command \
  -a "podman pod ps && podman ps --filter pod=easygreen-pod" \
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

Pod startup failure:

```bash
incus exec u24 -- bash -lc 'podman pod ps; podman ps -a; podman logs easygreen-db || true; podman logs easygreen-app || true'
```
