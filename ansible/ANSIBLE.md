# ANSIBLE.md

Ansible operations guide for the **easy-green-project** backend.
Covers security hardening, VM bootstrapping, and application deployment to the Multipass staging VM.

---

## Directory layout

```
easy-green-project/
├── ansible.cfg
└── ansible/
    ├── harden.yml              ← Step 1: one-time VPS security hardening
    ├── bootstrap-compose.yml   ← Step 2: one-time Podman setup
    ├── deploy-compose.yml      ← Step 3: run on every deploy
    ├── deploy.yml              ← existing deploy playbook
    ├── bootstrap.yml           ← existing bootstrap playbook
    ├── rollback.yml            ← existing rollback playbook
    ├── vault.yml               ← Ansible Vault secrets
    ├── inventory/
    │   ├── staging.ini         ← Multipass VM (u24 @ 10.250.127.182)
    │   └── production.ini      ← production VPS
    ├── group_vars/
    └── roles/
```

---

## Inventories

### Staging (Multipass VM)

```ini
# ansible/inventory/staging.ini
[staging]
u24 ansible_host=10.250.127.182 \
    ansible_user=ubuntu \
    ansible_ssh_private_key_file=/home/smahi/.ssh/id_rsa_multipass \
    ansible_python_interpreter=auto_silent

[staging:vars]
env=staging
```

### Production

```ini
# ansible/inventory/production.ini
[production]
vps ansible_host=YOUR_SERVER_IP \
    ansible_user=ubuntu \
    ansible_ssh_private_key_file=/home/smahi/.ssh/id_rsa_production \
    ansible_python_interpreter=auto_silent

[production:vars]
env=production
```

---

## The three playbooks

### 1. `harden.yml` — VPS security hardening

Run **once** on any fresh server before anything else. Applies 14 hardening layers:

| Layer | What it does |
|---|---|
| System update | Full upgrade before anything is configured |
| Package hygiene | Removes `telnet`, `rsh`, `nis`; installs `ufw`, `fail2ban`, `auditd`, `rkhunter` |
| SSH | Pubkey-only auth, no root login, modern ciphers, 3 max retries, no X11/TCP forwarding |
| UFW firewall | Default deny-in; allows only SSH, 80, 443 |
| Fail2ban | Bans IPs after 5 failed SSH attempts for 1 hour |
| Sysctl (25 settings) | SYN flood protection, full ASLR, disable IP redirects, restrict ptrace/dmesg/kptr |
| `/tmp` + `/dev/shm` | Mounted `noexec,nosuid,nodev` — blocks malware executing from temp dirs |
| Unattended-upgrades | Security patches apply automatically |
| Auditd | Logs changes to passwd/shadow/sudoers/SSH and all privileged commands |
| Kernel module blacklist | Disables DCCP, SCTP, firewire, USB storage — unused on a VPS |
| Cron restriction | Non-root users cannot schedule jobs |
| PAM / password policy | 14-char minimum, complexity rules, 90-day expiry |
| Rkhunter | Rootkit scanner with weekly cron, baseline set on install |
| Login banner | Legal warning displayed on SSH connect |

#### Key variables (edit at the top of `harden.yml`)

```yaml
ssh_port: 22                    # change to e.g. 2222 to move off default
ssh_allowed_users:
  - ubuntu
ssh_pubkey_only: true

fail2ban_ssh_maxretry: 5
fail2ban_ssh_bantime:  3600     # 1 hour
fail2ban_ssh_findtime:  600     # 10-minute window

unattended_upgrades_email: ""   # set your email for patch reports
```

---

### 2. `bootstrap-compose.yml` — Podman setup

Run **once** after hardening. Prepares the VM to run the compose stack:

- Installs `podman` and `podman-compose`
- Allows rootless binding to port 80 (`net.ipv4.ip_unprivileged_port_start=80`)
- Writes `~/.config/containers/containers.conf` with reliable DNS (`1.1.1.1`, `8.8.8.8`)
- Creates project directories (`~/easy-green-project/backend/`, `backups/`)
- Enables the Podman socket as a user service
- Enables linger so user services survive logout
- Leaves deploy-time service registration to `deploy-compose.yml`

---

### 3. `deploy-compose.yml` — Application deploy

Run **on every deploy**. Syncs code, builds the image, and restarts containers:

1. Rsyncs `backend/` to the VM (excludes `node_modules`, `vendor`, `.env`, built assets)
2. Copies `.env` once — `force: false` means it never overwrites an existing `.env` on the server
3. Builds `myapp_app:latest` via `podman build`
4. Brings up `db` + `valkey` if not already running, waits for PostgreSQL to be healthy
5. Removes and recreates `app`, `queue`, `scheduler` containers from the fresh image
6. Ensures `caddy` is up
7. Installs and enables a `systemd --user` unit so `podman-compose up -d` is re-run automatically after every reboot
8. Smoke-tests `http://<vm-ip>` — fails the playbook if the app doesn't respond

The app container's entrypoint automatically runs on every start:
- `php artisan migrate --force`
- `php artisan filament:cache-components`
- `php artisan icons:cache`
- `php artisan optimize`
- `php artisan storage:link`

#### Selective tags

```bash
# Sync code + build image only (no restart)
--tags build

# Restart app containers only (skip build)
--tags restart

# Run smoke test only
--tags smoke
```

---

## Workflow

### First time — fresh server

```
harden.yml → reboot → bootstrap-compose.yml → deploy-compose.yml
```

```bash
# 1. Harden
ansible-playbook ansible/harden.yml -i ansible/inventory/staging.ini

# 2. Reboot to apply kernel / module changes
ansible staging -i ansible/inventory/staging.ini -m reboot

# 3. Bootstrap Podman
ansible-playbook ansible/bootstrap-compose.yml -i ansible/inventory/staging.ini

# 4. Deploy the app
ansible-playbook ansible/deploy-compose.yml -i ansible/inventory/staging.ini
```

### Every deploy after that

```bash
ansible-playbook ansible/deploy-compose.yml -i ansible/inventory/staging.ini
```

### Deploy to production

```bash
ansible-playbook ansible/harden.yml          -i ansible/inventory/production.ini
ansible production -i ansible/inventory/production.ini -m reboot
ansible-playbook ansible/bootstrap-compose.yml -i ansible/inventory/production.ini
ansible-playbook ansible/deploy-compose.yml  -i ansible/inventory/production.ini
```

---

## Common operations

### Check connectivity

```bash
ansible staging -i ansible/inventory/staging.ini -m ping
```

### Run an artisan command remotely

```bash
ansible staging -i ansible/inventory/staging.ini -m command \
  -a "podman exec myapp_app_1 php artisan migrate:status" \
  --become=false
```

### Tail logs on the VM

```bash
ansible staging -i ansible/inventory/staging.ini -m command \
  -a "podman logs --tail=50 myapp_app_1" \
  --become=false
```

### Open a shell on the VM

```bash
ssh -i ~/.ssh/id_rsa_multipass ubuntu@10.250.127.182
```

### Force a full image rebuild (no cache)

```bash
ansible staging -i ansible/inventory/staging.ini -m command \
  -a "podman build --no-cache -f backend/docker/app.Dockerfile -t myapp_app:latest backend/" \
  --become=false
```

### Manual database backup

```bash
ansible staging -i ansible/inventory/staging.ini -m command \
  -a "bash /home/ubuntu/easy-green-project/backend/deploy.sh backup" \
  --become=false
```

---

## Troubleshooting

### SSH connection refused after hardening

If you changed `ssh_port`, update the inventory:
```ini
u24 ansible_host=10.250.127.182 ansible_port=2222 ...
```
And ensure UFW allows the new port — check `harden.yml` `ufw_allowed_ports`.

### `community.general.ufw` module not found

```bash
ansible-galaxy collection install community.general
ansible-galaxy collection install ansible.posix
```

### Rsync fails (module not found on VM)

```bash
# Install rsync on the VM first
ansible staging -i ansible/inventory/staging.ini -m apt \
  -a "name=rsync state=present" --become
```

### Deploy smoke test fails (app not responding)

Check the app container logs directly on the VM:
```bash
ssh -i ~/.ssh/id_rsa_multipass ubuntu@10.250.127.182 \
  "podman logs myapp_app_1 --tail=50"
```

Common causes and fixes:

| Symptom | Cause | Fix |
|---|---|---|
| Container keeps restarting | `APP_KEY` missing or bad | Run `php artisan key:generate --show` locally, paste into `.env` |
| `migrate` fails | Wrong DB credentials | Check `DB_*` in `.env` matches `POSTGRES_*` in `compose.yaml` |
| 502 from Caddy | App crashed after start | Check entrypoint logs — usually a bad env var |
| Port 80 refused | Podman rootless port | Re-run `bootstrap-compose.yml` |
| DNS fails during build | Container DNS broken | Re-run `bootstrap-compose.yml` — it writes `containers.conf` |

---

## Security notes

- **Never commit `.env`** — the deploy playbook copies it with `force: false` so it is written once and never overwritten by Ansible after that. Rotate secrets directly on the server.
- **Auditd `-e 2`** (immutable rules) is commented out in `harden.yml`. Uncomment it on production once your audit rules are finalised — after that, rule changes require a reboot.
- **SSH port** defaults to `22`. For production, change `ssh_port` in `harden.yml` to a non-standard port, update the inventory with `ansible_port`, and re-run the firewall tasks.
- **Rkhunter baseline** is set immediately after install. Re-run `rkhunter --propupd` manually after any intentional system change (package upgrade, etc.) to avoid false positives.
- **Unattended-upgrades** applies security patches automatically but does **not** reboot automatically (`Automatic-Reboot "false"`). Schedule a monthly maintenance window to reboot and pick up kernel updates.
