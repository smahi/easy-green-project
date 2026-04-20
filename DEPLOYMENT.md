# DEPLOYMENT.md

Deployment guide for **Laravel 12 + Filament 5** on a self-managed server using Podman and Podman Compose.

---

## Stack overview

| Service | Image | Role |
|---|---|---|
| `app` | `myapp_app:latest` (built locally) | FrankenPHP — serves PHP over HTTP on port 8080 |
| `caddy` | `caddy:2-alpine` | Reverse proxy, TLS termination |
| `db` | `postgres:18-alpine` | Primary database |
| `valkey` | `valkey/valkey:8-alpine` | Cache, sessions, queues |
| `queue` | `myapp_app:latest` | Runs `php artisan queue:work` |
| `scheduler` | `myapp_app:latest` | Runs `php artisan schedule:work` |

---

## File layout

These files live in your project root alongside `artisan`:

```
your-project/
├── compose.yaml
├── deploy.sh
├── .env                      ← never commit this
├── .env.production           ← template, safe to commit
├── .dockerignore
├── caddy/
│   ├── Caddyfile             ← production (HTTPS, Let's Encrypt)
│   └── Caddyfile.dev         ← local / VM (HTTP only)
└── docker/
    ├── app.Dockerfile
    ├── Caddyfile             ← FrankenPHP inner config
    ├── entrypoint.sh
    └── php.ini
```

---

## Part 1 — Local development (Multipass VM)

### 1. Install Podman

```bash
# Ubuntu / Debian
sudo apt install -y podman podman-compose

# Fedora / RHEL
sudo dnf install -y podman podman-compose
```

Verify:
```bash
podman --version
podman-compose --version
```

### 2. Allow binding to port 80

Rootless Podman cannot bind ports below 1024 by default:

```bash
echo 'net.ipv4.ip_unprivileged_port_start=80' | sudo tee /etc/sysctl.d/99-podman-ports.conf
sudo sysctl -p /etc/sysctl.d/99-podman-ports.conf
```

This persists across reboots.

### 3. Fix DNS inside build containers (if apk/apt fails during build)

```bash
mkdir -p ~/.config/containers
cp containers.conf ~/.config/containers/containers.conf
systemctl --user restart podman.socket
```

### 4. Configure environment

```bash
cp .env.production .env
nano .env
```

Minimum required changes for local dev:

```dotenv
APP_URL=http://<vm-ip>
APP_DEBUG=true

DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=a-strong-password

VALKEY_PASSWORD=another-strong-password
REDIS_PASSWORD=another-strong-password   # must match VALKEY_PASSWORD

SESSION_SECURE_COOKIE=false              # no HTTPS locally
```

Get your VM IP:
```bash
multipass info <vm-name>
# or
ip a | grep inet
```

### 5. Configure Caddy for local (no TLS)

In `compose.yaml`, point the Caddy volume at the dev Caddyfile:

```yaml
caddy:
  volumes:
    - ./caddy/Caddyfile.dev:/etc/caddy/Caddyfile:ro   # ← dev
    #- ./caddy/Caddyfile:/etc/caddy/Caddyfile:ro      # ← production
  ports:
    - "80:80"
    # 443 not needed locally
```

### 6. First deploy

```bash
chmod +x deploy.sh
./deploy.sh
```

The script will:
1. Build the `myapp_app:latest` image (3-stage: Composer → Node/Vite → FrankenPHP)
2. Start all services via `podman-compose up -d`
3. On first start the app container automatically runs:
   - `php artisan migrate --force`
   - `php artisan filament:cache-components`
   - `php artisan icons:cache`
   - `php artisan optimize`
   - `php artisan storage:link`

Visit `http://<vm-ip>` — you should see your Laravel app.

---

## Part 2 — Production server

### 1. Provision a server

A basic VPS (2 vCPU, 2 GB RAM) is enough to start. Supported OS: Ubuntu 22.04+, Debian 12+, Fedora 39+.

### 2. Install Podman

```bash
sudo apt install -y podman podman-compose
echo 'net.ipv4.ip_unprivileged_port_start=80' | sudo tee /etc/sysctl.d/99-podman-ports.conf
sudo sysctl -p /etc/sysctl.d/99-podman-ports.conf
```

### 3. Point your DNS

Create an A record:
```
yourdomain.com     →  <server-public-ip>
www.yourdomain.com →  <server-public-ip>
```

Wait for propagation before deploying (Caddy needs to reach Let's Encrypt).

### 4. Clone the repo

```bash
git clone https://github.com/you/myapp.git /srv/myapp
cd /srv/myapp
```

### 5. Configure environment

```bash
cp .env.production .env
nano .env
```

Key production values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

SESSION_SECURE_COOKIE=true
```

Fill in all `TODO_` placeholders. `APP_KEY` is pre-generated in `.env.production` — keep it secret and back it up.

### 6. Switch Caddy to production mode

In `compose.yaml`:

```yaml
caddy:
  volumes:
    #- ./caddy/Caddyfile.dev:/etc/caddy/Caddyfile:ro  # ← disable dev
    - ./caddy/Caddyfile:/etc/caddy/Caddyfile:ro        # ← enable production
  ports:
    - "80:80"
    - "443:443"
    - "443:443/udp"   # HTTP/3
```

Edit `caddy/Caddyfile` and replace `yourdomain.com` with your real domain.

### 7. Deploy

```bash
chmod +x deploy.sh
./deploy.sh
```

Caddy will automatically obtain a TLS certificate from Let's Encrypt on first start. Visit `https://yourdomain.com`.

---

## Part 3 — Daily operations

All operations go through `deploy.sh`:

### Deploy a new version

```bash
./deploy.sh pull
```

This runs `git pull`, rebuilds the image, restarts all services, and runs migrations automatically.

### Run artisan commands

```bash
./deploy.sh artisan migrate --status
./deploy.sh artisan tinker
./deploy.sh artisan cache:clear
./deploy.sh artisan queue:retry all
```

### Open a shell inside the app container

```bash
./deploy.sh shell
```

### Tail logs

```bash
./deploy.sh logs          # all services
./deploy.sh logs app      # app only
./deploy.sh logs caddy    # caddy / request log
./deploy.sh logs db       # postgres
./deploy.sh logs queue    # queue worker
```

### Stop everything (data is preserved)

```bash
./deploy.sh down
```

### Manual database backup

```bash
./deploy.sh backup
# Saves to: ./backups/pg_YYYYMMDD_HHMMSS.sql.gz
```

---

## Part 4 — Auto-start on boot

Podman doesn't use a daemon, so you need a systemd user service to restart containers after a reboot.

```bash
# Generate systemd units for all running containers
mkdir -p ~/.config/systemd/user
podman generate systemd --new --name --files myapp_app_1
mv *.service ~/.config/systemd/user/

# Enable the project to start on boot
systemctl --user enable container-myapp_app_1.service
systemctl --user enable container-myapp_db_1.service
systemctl --user enable container-myapp_valkey_1.service
systemctl --user enable container-myapp_caddy_1.service
systemctl --user enable container-myapp_queue_1.service
systemctl --user enable container-myapp_scheduler_1.service

# Allow user services to run without being logged in
sudo loginctl enable-linger $USER
```

---

## Part 5 — Automated backups (cron)

```bash
crontab -e
```

Add:
```cron
# Daily backup at 3 AM
0 3 * * * cd /srv/myapp && ./deploy.sh backup >> /srv/myapp/backups/cron.log 2>&1
```

For off-site backups, use `restic` or `rclone` to ship the `backups/` folder to S3 / Backblaze B2 after the dump.

---

## Part 6 — Troubleshooting

### App container keeps restarting

```bash
podman logs myapp_app_1
```

Common causes:
- `APP_KEY` is empty or invalid → run `php artisan key:generate --show` locally and paste into `.env`
- DB credentials wrong → check `DB_*` values match `POSTGRES_*` in compose environment
- Migrations failing → check for pending manual steps in your migration files

### Caddy shows default page

The volume mount destination must always be `/etc/caddy/Caddyfile`:
```yaml
# ✅ correct
- ./caddy/Caddyfile.dev:/etc/caddy/Caddyfile:ro

# ❌ wrong — Caddy ignores this
- ./caddy/Caddyfile:/etc/caddy/Caddyfile.dev:ro
```

### 502 Bad Gateway

Caddy is up but can't reach the app. Check:
```bash
podman ps --format "table {{.Names}}\t{{.Status}}"
podman logs myapp_app_1
```

### DNS failure during image build

```bash
mkdir -p ~/.config/containers
cp containers.conf ~/.config/containers/containers.conf
systemctl --user restart podman.socket
```

### Port 80/443 permission denied

```bash
echo 'net.ipv4.ip_unprivileged_port_start=80' | sudo tee /etc/sysctl.d/99-podman-ports.conf
sudo sysctl -p /etc/sysctl.d/99-podman-ports.conf
```

### Force a clean rebuild (no cache)

```bash
podman build --no-cache -f docker/app.Dockerfile -t myapp_app:latest .
podman-compose up -d --remove-orphans
```

---

## Quick reference

```bash
./deploy.sh              # build + start everything
./deploy.sh pull         # git pull + rebuild + restart
./deploy.sh logs [svc]   # tail logs
./deploy.sh artisan …    # run artisan
./deploy.sh shell        # bash in app container
./deploy.sh backup       # dump postgres
./deploy.sh down         # stop all containers
```
