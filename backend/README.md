# Laravel 12 + Filament 5 – Production Deploy (Podman)

A clean, production-grade container setup for a solo dev.

## Stack

| Layer | Image |
|---|---|
| App server | FrankenPHP (PHP 8.4 / upgrade tag when 8.5 ships) |
| Reverse proxy | Caddy 2 (auto-TLS via Let's Encrypt) |
| Database | PostgreSQL 18 |
| Cache / Queue / Sessions | Valkey 8 (Redis-compatible) |

---

## Directory layout

```
.                          ← your Laravel project root
├── compose.yaml
├── deploy.sh
├── .env.production        ← template; copy → .env, never commit .env
├── caddy/
│   └── Caddyfile          ← reverse-proxy config (edit your domain here)
└── docker/
    ├── app.Dockerfile
    ├── Caddyfile          ← FrankenPHP inner Caddyfile (app server)
    ├── entrypoint.sh
    └── php.ini
```

---

## First-time setup

### 1. Install Podman + Podman Compose

```bash
# Fedora / RHEL
sudo dnf install -y podman podman-compose

# Ubuntu / Debian
sudo apt install -y podman podman-compose
# or: pip install podman-compose
```

### 2. Clone your repo on the server

```bash
git clone https://github.com/you/myapp.git /srv/myapp
cd /srv/myapp
```

### 3. Copy these deploy files into your project root

Copy everything from this `deploy/` folder into the root of your Laravel project so `compose.yaml` is alongside `artisan`.

### 4. Configure your environment

```bash
cp .env.production .env
nano .env          # fill in APP_KEY, DB_PASSWORD, VALKEY_PASSWORD, domain, mail…
```

Generate a key if you don't have one:
```bash
# On your local machine:
php artisan key:generate --show
# Paste the output into APP_KEY in .env
```

### 5. Edit your domain

In `caddy/Caddyfile`, replace `yourdomain.com` with your real domain.  
Make sure your DNS A record points to your server's public IP **before** first start so Caddy can obtain a TLS certificate.

### 6. Deploy

```bash
chmod +x deploy.sh
./deploy.sh
```

That's it. The entrypoint automatically runs:
- `php artisan migrate --force`
- `php artisan filament:cache-components`
- `php artisan icons:cache`
- `php artisan optimize`
- `php artisan storage:link`

---

## Daily operations

```bash
./deploy.sh pull           # git pull + rebuild + restart
./deploy.sh logs           # tail all logs
./deploy.sh logs app       # tail app logs only
./deploy.sh artisan tinker # run artisan commands
./deploy.sh shell          # bash inside the app container
./deploy.sh backup         # dump PostgreSQL → ./backups/
./deploy.sh down           # stop everything (data preserved)
```

---

## Laravel config for this stack

Make sure your `config/database.php` uses `pgsql` as the default connection.

In `.env` the key settings are:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=db               # service name from compose.yaml
REDIS_HOST=valkey        # Valkey is Redis-protocol compatible
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

---

## PHP extensions included

| Extension | Why |
|---|---|
| `bcmath` | Laravel Money / calculations |
| `exif` | Filament image handling |
| `gd` | Image processing (thumbnails) |
| `imagick` | Advanced image operations |
| `intl` | Locale / number formatting |
| `pcntl` | Queue signal handling |
| `pdo_pgsql` + `pgsql` | PostgreSQL |
| `redis` | Valkey (Redis-protocol compatible) |
| `zip` | Package / export features |
| `opcache` + JIT | Performance (built in) |
| `sodium` | Encryption (built in) |

---

## Backups

A quick manual backup:
```bash
./deploy.sh backup
# Creates: backups/pg_20260418_120000.sql.gz
```

For automated backups, add a cron job on the host:
```cron
0 3 * * * /srv/myapp/deploy.sh backup
```

Or use `restic` / `borgbackup` to ship the `backups/` folder off-site.

---

## Upgrading

```bash
git pull                  # get new app code
./deploy.sh deploy        # rebuild images + restart
```

Migrations run automatically on each deploy. They are idempotent, so re-running is safe.

---

## Podman-specific notes

- **Rootless**: Run everything as your regular user. No `sudo` needed after install.
- **Socket**: If podman-compose can't find the socket, run `systemctl --user enable --now podman.socket`.
- **Ports < 1024**: Rootless Podman can't bind port 80/443 by default.  
  Fix: `echo "net.ipv4.ip_unprivileged_port_start=80" | sudo tee /etc/sysctl.d/99-podman-ports.conf && sudo sysctl -p`
- **Auto-start on boot**: `systemctl --user enable --now podman-compose@myapp` (requires a systemd unit) or use `podman generate systemd`.

---

## Security checklist

- [ ] `.env` has `APP_DEBUG=false`
- [ ] Strong random passwords for `DB_PASSWORD` and `VALKEY_PASSWORD`
- [ ] `APP_KEY` is set and backed up
- [ ] Firewall: only ports 80 and 443 are publicly open; 5432 and 6379 are internal only
- [ ] `./deploy.sh backup` is running on a cron, with off-site copy
- [ ] Caddy logs are reviewed occasionally for anomalies
