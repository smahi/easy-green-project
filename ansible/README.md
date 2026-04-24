# EASY GREEN - Ansible Operations

Single-source guide for provisioning, deploying, backing up, and restoring the
Easy Green backend.

## Playbooks

```text
ansible/
├── harden.yml              # One-time host hardening
├── bootstrap-compose.yml   # One-time Podman/rootless setup
├── deploy-compose.yml      # Main app deploy playbook
├── backup-download.yml     # Download a sanitized backend backup
├── restore-backup.yml      # Restore a selected backend backup
├── rollback.yml            # Existing rollback helper
├── inventory/
│   ├── staging.ini
│   └── production.ini
├── group_vars/
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

1. Edit `ansible/vault.yml` with real secrets, then encrypt it if needed.
2. Verify the inventory you want to target.
3. Generate an app key locally and store it in the vault.

```bash
ansible-vault edit ansible/vault.yml
cd backend && php artisan key:generate --show
```

## Run Order

For a fresh server, run the playbooks in this order:

```text
harden.yml -> reboot -> bootstrap-compose.yml -> deploy-compose.yml
```

Staging example:

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/harden.yml
ansible staging -i ansible/inventory/staging.ini -m reboot
ansible-playbook -i ansible/inventory/staging.ini ansible/bootstrap-compose.yml
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-compose.yml
```

Production example:

```bash
ansible-playbook -i ansible/inventory/production.ini ansible/harden.yml
ansible production -i ansible/inventory/production.ini -m reboot
ansible-playbook -i ansible/inventory/production.ini ansible/bootstrap-compose.yml
ansible-playbook -i ansible/inventory/production.ini ansible/deploy-compose.yml
```

For later application releases, run only:

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-compose.yml
```

## What Each Playbook Does

### `harden.yml`

Run once before anything else on a new host.

- updates packages
- hardens SSH
- configures UFW and Fail2ban
- applies sysctl settings
- hardens `/tmp` and `/dev/shm`
- enables unattended upgrades, auditd, and other baseline protections

Note: `/tmp` is managed through `systemd` `tmp.mount`. The playbook reloads
`systemd` and restarts `tmp.mount` when the drop-in changes, instead of calling
`mount -o remount /tmp`.

### `bootstrap-compose.yml`

Run once after hardening.

- installs `podman` and `podman-compose`
- enables rootless low-port binding
- writes container DNS configuration
- creates project directories
- enables the user Podman socket and linger

### `deploy-compose.yml`

Run on every deploy.

- syncs `backend/` to the host
- preserves the existing remote `.env`
- builds the app image
- starts or refreshes the Podman Compose stack
- re-enables the user `systemd` service
- smoke-tests the deployed app

Useful tags:

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-compose.yml --tags build
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-compose.yml --tags restart
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-compose.yml --tags smoke
```

### `backup-download.yml`

Run when you want a local copy of the deployed backend.

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/backup-download.yml
```

Behavior:

- creates a sanitized archive on the remote host
- downloads it to `ansible/backups/<inventory_hostname>/`
- removes the temporary remote archive by default
- excludes `.env` unless `backup_include_env=true`

Include `.env` only when you explicitly want it:

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/backup-download.yml \
  -e backup_include_env=true
```

### `restore-backup.yml`

Run when you need to restore a downloaded backend archive.

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/restore-backup.yml \
  -e restore_backup_file=ansible/backups/u24/easy-green-backend-u24-20260424T051901.tar.gz
```

Behavior:

- validates the selected local archive first
- creates a pre-restore rollback snapshot remotely
- restores the backend while preserving the live `.env` by default
- rebuilds and restarts the stack
- verifies artisan and HTTP health checks

Restore the backup `.env` only if the archive contains one and you intend to use it:

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/restore-backup.yml \
  -e restore_backup_file=ansible/backups/u24/your-backup.tar.gz \
  -e restore_include_env=true
```

## Inventories

`ansible/inventory/staging.ini` targets the Multipass VM.

`ansible/inventory/production.ini` targets the production VPS.

If you change the SSH port in `harden.yml`, update the inventory host entry to
match.

## Common Operations

Connectivity check:

```bash
ansible staging -i ansible/inventory/staging.ini -m ping
```

Remote artisan command:

```bash
ansible staging -i ansible/inventory/staging.ini -m command \
  -a "podman exec myapp_app_1 php artisan migrate:status" \
  --become=false
```

Tail app logs:

```bash
ansible staging -i ansible/inventory/staging.ini -m command \
  -a "podman logs --tail=50 myapp_app_1" \
  --become=false
```

## Troubleshooting

`community.general.ufw` or `ansible.posix` missing:

```bash
ansible-galaxy collection install community.general ansible.posix
```

`rsync` missing on target:

```bash
ansible staging -i ansible/inventory/staging.ini -m apt \
  -a "name=rsync state=present" --become
```

Smoke test fails after deploy:

```bash
ssh -i ~/.ssh/id_rsa_multipass ubuntu@10.250.127.182 \
  "podman logs myapp_app_1 --tail=50"
```
