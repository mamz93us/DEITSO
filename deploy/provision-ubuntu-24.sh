#!/usr/bin/env bash
#
# DEITAM — VPS provisioning for Ubuntu 24.04 LTS.
#
# Brings a fresh Ubuntu 24.04 server up to a state that can run the
# DEITAM Laravel + Filament + Caddy stack end-to-end:
#
#   PHP 8.3 + extensions  ·  MySQL 8 (or MariaDB)  ·  Redis 7
#   Composer 2  ·  Node 20 LTS  ·  Caddy v2 (on-demand TLS)
#   systemd units for Horizon, Reverb, the Laravel scheduler
#   UFW with only 22 / 80 / 443 open
#
# Usage:
#   1. Provision a fresh Ubuntu 24.04 VPS, log in as a sudo user.
#   2. Copy this script (or clone the repo).
#   3. Fill in the CONFIG block below — or pass values as env vars.
#   4. Run as root:    sudo -E bash deploy/provision-ubuntu-24.sh
#
# The script is idempotent. Re-run any time; only diffs are applied.
#

set -euo pipefail

#============================== CONFIG =================================
# Override any of these by exporting them before running:
#   export GIT_REPO=git@github.com:you/deitam.git
#   sudo -E bash deploy/provision-ubuntu-24.sh

APP_USER="${APP_USER:-deitam}"
APP_DIR="${APP_DIR:-/var/www/deitam}"

GIT_REPO="${GIT_REPO:-}"                        # e.g. git@github.com:you/deitam.git
GIT_BRANCH="${GIT_BRANCH:-master}"
GIT_DEPLOY_KEY="${GIT_DEPLOY_KEY:-}"            # optional path to private SSH key

APP_DOMAIN="${APP_DOMAIN:-}"                    # primary host, e.g. app.deevar.cloud
APP_PLATFORM_BASE_DOMAIN="${APP_PLATFORM_BASE_DOMAIN:-${APP_DOMAIN}}"
LE_EMAIL="${LE_EMAIL:-}"                        # Let's Encrypt contact email

DB_FLAVOUR="${DB_FLAVOUR:-mysql}"               # mysql | mariadb
DB_NAME="${DB_NAME:-deitam}"
DB_USER="${DB_USER:-deitam}"
DB_PASS="${DB_PASS:-}"                          # empty → auto-generate strong pw

TIMEZONE="${TIMEZONE:-Africa/Cairo}"
PHP_VERSION="${PHP_VERSION:-8.3}"
NODE_MAJOR="${NODE_MAJOR:-20}"
REVERB_PORT="${REVERB_PORT:-8080}"
INTERNAL_CALLBACK_PORT="${INTERNAL_CALLBACK_PORT:-8001}"

#============================== PRE-FLIGHT =============================
[[ $EUID -eq 0 ]] || { echo "Run as root (use sudo -E)"; exit 1; }

. /etc/os-release
if [[ "${VERSION_ID:-}" != "24.04" ]]; then
    echo "Warning: tested on Ubuntu 24.04 LTS (you have ${PRETTY_NAME:-unknown})."
    read -rp "Continue anyway? [y/N] " yn
    [[ "${yn:-}" =~ ^[Yy]$ ]] || exit 1
fi

for v in GIT_REPO APP_DOMAIN LE_EMAIL; do
    if [[ -z "${!v}" ]]; then
        echo "ERROR: \$${v} is unset. Edit the CONFIG block or export it."
        exit 1
    fi
done

[[ -z "${DB_PASS}" ]] && DB_PASS="$(openssl rand -base64 36 | tr -dc 'A-Za-z0-9' | head -c 32)"

export DEBIAN_FRONTEND=noninteractive
log() { printf '\n\033[1;34m==>\033[0m %s\n' "$*"; }

#============================== APT BASE ===============================
log "Updating APT index"
apt-get update -y

log "Installing base packages"
apt-get install -y --no-install-recommends \
    curl wget git unzip jq ca-certificates gnupg lsb-release \
    software-properties-common pkg-config build-essential ufw acl openssl

log "Setting timezone to ${TIMEZONE}"
timedatectl set-timezone "${TIMEZONE}"

#============================== PHP 8.3 ================================
# Ubuntu 24.04 ships PHP 8.3 in the default repos.
log "Installing PHP ${PHP_VERSION} + extensions"
apt-get install -y --no-install-recommends \
    "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-cli" "php${PHP_VERSION}-common" \
    "php${PHP_VERSION}-mysql" "php${PHP_VERSION}-redis" "php${PHP_VERSION}-curl" \
    "php${PHP_VERSION}-mbstring" "php${PHP_VERSION}-xml" "php${PHP_VERSION}-zip" \
    "php${PHP_VERSION}-bcmath" "php${PHP_VERSION}-intl" "php${PHP_VERSION}-gd" \
    "php${PHP_VERSION}-exif" "php${PHP_VERSION}-pcntl" "php${PHP_VERSION}-soap" \
    "php${PHP_VERSION}-opcache" "php${PHP_VERSION}-imagick"

cat > "/etc/php/${PHP_VERSION}/fpm/conf.d/99-deitam.ini" <<EOF
; DEITAM PHP tuning — overrides Ubuntu defaults for Filament workloads.
memory_limit = 512M
upload_max_filesize = 50M
post_max_size = 60M
max_execution_time = 60
date.timezone = ${TIMEZONE}
expose_php = Off

; OPcache — production settings. Run \`systemctl reload php${PHP_VERSION}-fpm\`
; (or restart) after each deploy to invalidate the cache.
opcache.enable = 1
opcache.enable_cli = 0
opcache.memory_consumption = 192
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.save_comments = 1
EOF
cp "/etc/php/${PHP_VERSION}/fpm/conf.d/99-deitam.ini" "/etc/php/${PHP_VERSION}/cli/conf.d/99-deitam.ini"

systemctl enable --now "php${PHP_VERSION}-fpm"

#============================== DATABASE ===============================
if [[ "${DB_FLAVOUR}" == "mariadb" ]]; then
    log "Installing MariaDB"
    apt-get install -y mariadb-server
    systemctl enable --now mariadb
    MYSQL_CMD=(mysql)
else
    log "Installing MySQL 8"
    apt-get install -y mysql-server
    systemctl enable --now mysql
    MYSQL_CMD=(mysql)
fi

log "Creating database '${DB_NAME}' and user '${DB_USER}'"
"${MYSQL_CMD[@]}" <<EOF
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF

#============================== REDIS ==================================
log "Installing Redis"
apt-get install -y redis-server
sed -i 's|^supervised .*|supervised systemd|' /etc/redis/redis.conf
sed -i 's|^# maxmemory-policy .*|maxmemory-policy allkeys-lru|' /etc/redis/redis.conf
systemctl enable --now redis-server

#============================== NODE.JS ================================
log "Installing Node.js ${NODE_MAJOR} LTS"
if ! command -v node >/dev/null 2>&1 || ! node -v | grep -q "^v${NODE_MAJOR}\."; then
    curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash -
    apt-get install -y nodejs
fi

#============================== COMPOSER ===============================
log "Installing Composer 2"
if ! command -v composer >/dev/null 2>&1; then
    EXPECTED=$(curl -fsSL https://composer.github.io/installer.sig)
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    ACTUAL=$(php -r "echo hash_file('SHA384', '/tmp/composer-setup.php');")
    [[ "${EXPECTED}" == "${ACTUAL}" ]] || { echo "Composer installer signature mismatch — aborting"; exit 1; }
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --quiet
    rm -f /tmp/composer-setup.php
fi

#============================== CADDY ==================================
log "Installing Caddy v2"
if ! command -v caddy >/dev/null 2>&1; then
    apt-get install -y debian-keyring debian-archive-keyring apt-transport-https
    curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' \
        | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
    curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' \
        > /etc/apt/sources.list.d/caddy-stable.list
    apt-get update -y
    apt-get install -y caddy
fi

#============================== APP USER + CODE ========================
log "Creating app user '${APP_USER}'"
if ! id "${APP_USER}" >/dev/null 2>&1; then
    useradd --system --create-home --shell /bin/bash --home-dir "/home/${APP_USER}" "${APP_USER}"
fi
usermod -aG www-data "${APP_USER}"

mkdir -p "${APP_DIR}"
chown -R "${APP_USER}:www-data" "${APP_DIR}"

# Optional deploy key
if [[ -n "${GIT_DEPLOY_KEY}" && -f "${GIT_DEPLOY_KEY}" ]]; then
    log "Installing deploy SSH key"
    install -d -m 700 -o "${APP_USER}" -g "${APP_USER}" "/home/${APP_USER}/.ssh"
    install -m 600 -o "${APP_USER}" -g "${APP_USER}" "${GIT_DEPLOY_KEY}" "/home/${APP_USER}/.ssh/id_ed25519"
    sudo -u "${APP_USER}" ssh-keyscan -t rsa,ed25519 github.com 2>/dev/null >> "/home/${APP_USER}/.ssh/known_hosts"
fi

log "Cloning / fast-forwarding ${GIT_REPO}"
if [[ ! -d "${APP_DIR}/.git" ]]; then
    sudo -u "${APP_USER}" git clone --branch "${GIT_BRANCH}" "${GIT_REPO}" "${APP_DIR}"
else
    sudo -u "${APP_USER}" git -C "${APP_DIR}" fetch --all --prune
    sudo -u "${APP_USER}" git -C "${APP_DIR}" reset --hard "origin/${GIT_BRANCH}"
fi

#============================== .env ===================================
log "Configuring .env"
if [[ ! -f "${APP_DIR}/.env" ]]; then
    sudo -u "${APP_USER}" cp "${APP_DIR}/.env.example" "${APP_DIR}/.env"
fi

INTERNAL_TOKEN="$(openssl rand -hex 32)"
GREEN_API_SECRET="$(openssl rand -hex 32)"
GREEN_API_TOKEN="$(openssl rand -hex 32)"

# Set/overwrite each key idempotently. If the key exists in .env, rewrite the
# line in place; otherwise append. Awk is used instead of sed so values
# containing & / and other regex metachars round-trip safely.
set_env() {
    local key="$1" val="$2" file="${APP_DIR}/.env"
    sudo -u "${APP_USER}" env KEY="${key}" VAL="${val}" awk '
        BEGIN { k = ENVIRON["KEY"]; v = ENVIRON["VAL"]; found = 0 }
        $0 ~ "^" k "=" { print k "=" v; found = 1; next }
        { print }
        END { if (!found) print k "=" v }
    ' "${file}" > "${file}.new" && sudo -u "${APP_USER}" mv "${file}.new" "${file}"
}

set_env APP_ENV production
set_env APP_DEBUG false
set_env APP_URL "https://${APP_DOMAIN}"
set_env APP_PLATFORM_BASE_DOMAIN "${APP_PLATFORM_BASE_DOMAIN}"
set_env APP_TIMEZONE "${TIMEZONE}"
set_env DB_CONNECTION mysql
set_env DB_HOST 127.0.0.1
set_env DB_PORT 3306
set_env DB_DATABASE "${DB_NAME}"
set_env DB_USERNAME "${DB_USER}"
set_env DB_PASSWORD "${DB_PASS}"
set_env REDIS_HOST 127.0.0.1
set_env REDIS_PORT 6379
set_env SESSION_DRIVER redis
set_env CACHE_STORE redis
set_env QUEUE_CONNECTION redis
set_env INTERNAL_ROUTES_TOKEN "${INTERNAL_TOKEN}"
set_env INTERNAL_ALLOWED_IPS 127.0.0.1
set_env GREEN_API_WEBHOOK_SECRET "${GREEN_API_SECRET}"
set_env GREEN_API_WEBHOOK_TOKEN "${GREEN_API_TOKEN}"

# Reverb — websockets behind Caddy reverse-proxy
set_env REVERB_HOST "${APP_DOMAIN}"
set_env REVERB_PORT 443
set_env REVERB_SCHEME https

#============================== COMPOSER INSTALL =======================
log "composer install (production)"
sudo -u "${APP_USER}" bash -c "cd '${APP_DIR}' && composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist"

if ! grep -q '^APP_KEY=base64:' "${APP_DIR}/.env"; then
    log "Generating APP_KEY"
    sudo -u "${APP_USER}" bash -c "cd '${APP_DIR}' && php artisan key:generate --force"
fi

#============================== NPM BUILD ==============================
log "Building frontend assets"
sudo -u "${APP_USER}" bash -c "cd '${APP_DIR}' && npm ci && npm run build"

#============================== STORAGE PERMS ==========================
log "Setting storage / cache permissions"
# www-data (php-fpm) needs rw on storage and bootstrap/cache; ${APP_USER} owns
# the rest. ACL keeps it sane across future file creations.
chown -R "${APP_USER}:www-data" "${APP_DIR}"
find "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" -type d -exec chmod 2775 {} \;
find "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" -type f -exec chmod 0664 {} \;
setfacl -R  -m u:www-data:rwx "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
setfacl -dR -m u:www-data:rwx "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

#============================== MIGRATE + OPTIMIZE =====================
log "Running migrations"
sudo -u "${APP_USER}" bash -c "cd '${APP_DIR}' && php artisan migrate --force"

log "Caching config / routes / views"
sudo -u "${APP_USER}" bash -c "cd '${APP_DIR}' && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache"
sudo -u "${APP_USER}" bash -c "cd '${APP_DIR}' && php artisan icons:cache 2>/dev/null || true"
sudo -u "${APP_USER}" bash -c "cd '${APP_DIR}' && php artisan filament:optimize 2>/dev/null || php artisan filament:cache-components 2>/dev/null || true"
sudo -u "${APP_USER}" bash -c "cd '${APP_DIR}' && php artisan storage:link"

#============================== SYSTEMD UNITS ==========================
log "Installing systemd units"

cat > /etc/systemd/system/deitam-horizon.service <<EOF
[Unit]
Description=DEITAM — Laravel Horizon (queue supervisor)
After=network.target redis-server.service
Requires=redis-server.service

[Service]
Type=simple
User=${APP_USER}
Group=${APP_USER}
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php artisan horizon
ExecStop=/bin/kill -SIGTERM \$MAINPID
Restart=always
RestartSec=5
KillSignal=SIGTERM
TimeoutStopSec=120
StandardOutput=append:/var/log/deitam/horizon.log
StandardError=append:/var/log/deitam/horizon.log

[Install]
WantedBy=multi-user.target
EOF

cat > /etc/systemd/system/deitam-reverb.service <<EOF
[Unit]
Description=DEITAM — Laravel Reverb (WebSocket server)
After=network.target redis-server.service
Requires=redis-server.service

[Service]
Type=simple
User=${APP_USER}
Group=${APP_USER}
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php artisan reverb:start --host=127.0.0.1 --port=${REVERB_PORT}
Restart=always
RestartSec=5
StandardOutput=append:/var/log/deitam/reverb.log
StandardError=append:/var/log/deitam/reverb.log

[Install]
WantedBy=multi-user.target
EOF

cat > /etc/systemd/system/deitam-scheduler.service <<EOF
[Unit]
Description=DEITAM — Laravel scheduler (cron tick)

[Service]
Type=oneshot
User=${APP_USER}
Group=${APP_USER}
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php artisan schedule:run
EOF

cat > /etc/systemd/system/deitam-scheduler.timer <<EOF
[Unit]
Description=Run DEITAM scheduler every minute

[Timer]
OnCalendar=*-*-* *:*:00
AccuracySec=1s
Persistent=true

[Install]
WantedBy=timers.target
EOF

install -d -o "${APP_USER}" -g "${APP_USER}" /var/log/deitam
systemctl daemon-reload
systemctl enable --now deitam-horizon.service deitam-reverb.service deitam-scheduler.timer

#============================== CADDY CONFIG ===========================
log "Writing Caddyfile"

cat > /etc/caddy/Caddyfile <<EOF
{
    email ${LE_EMAIL}

    # Caddy queries this endpoint before issuing a cert for an unknown host.
    # The internal listener on 127.0.0.1:${INTERNAL_CALLBACK_PORT} below serves
    # /internal/domains/allow, which checks organization_domains.dns_status.
    on_demand_tls {
        ask http://127.0.0.1:${INTERNAL_CALLBACK_PORT}/internal/domains/allow
    }
}

# Loopback-only listener for the Caddy on-demand TLS "ask" callback.
# routes/internal.php enforces an IP allowlist (defaults to 127.0.0.1 only),
# so binding here means only Caddy itself can reach it.
:${INTERNAL_CALLBACK_PORT} {
    bind 127.0.0.1
    root * ${APP_DIR}/public
    php_fastcgi unix//run/php/php${PHP_VERSION}-fpm.sock
    file_server
}

# Public listener. The literal APP_DOMAIN gets a Let's Encrypt cert eagerly;
# the catch-all "https://" gets one on-demand for any tenant custom domain
# the "ask" callback approves.
https://${APP_DOMAIN}, https:// {
    tls {
        on_demand
    }

    root * ${APP_DIR}/public
    encode gzip zstd

    # Reverb (Pusher protocol) — reverse-proxy to the local Reverb server.
    @reverb {
        path /app/*
        path /apps/*
        path /broadcasting/auth
    }
    handle @reverb {
        reverse_proxy 127.0.0.1:${REVERB_PORT}
    }

    php_fastcgi unix//run/php/php${PHP_VERSION}-fpm.sock
    file_server

    log {
        output file /var/log/caddy/deitam-access.log {
            roll_size 100MiB
            roll_keep 5
            roll_keep_for 30d
        }
        format json
    }

    header {
        Strict-Transport-Security "max-age=31536000; includeSubDomains"
        X-Content-Type-Options    "nosniff"
        Referrer-Policy           "strict-origin-when-cross-origin"
        -Server
        -X-Powered-By
    }
}

# Plain HTTP — redirect to HTTPS.
http:// {
    redir https://{host}{uri} permanent
}
EOF

install -d -o caddy -g caddy /var/log/caddy
caddy fmt --overwrite /etc/caddy/Caddyfile
caddy validate --config /etc/caddy/Caddyfile

systemctl enable caddy
systemctl restart caddy

#============================== FIREWALL ===============================
log "Configuring UFW (ssh / http / https only)"
ufw --force enable
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw reload

#============================== HEALTHCHECK ============================
sleep 2
log "Health probe"
HEALTH_URL="http://127.0.0.1:${INTERNAL_CALLBACK_PORT}/up"
if curl -fs --max-time 10 "${HEALTH_URL}" >/dev/null; then
    echo "  ✓ ${HEALTH_URL} responded 200"
else
    echo "  ✗ ${HEALTH_URL} did not respond — check:"
    echo "      systemctl status caddy php${PHP_VERSION}-fpm"
    echo "      tail -n 50 ${APP_DIR}/storage/logs/laravel.log"
fi

#============================== DONE ===================================
cat <<EOF

  ───────────────────────────────────────────────────────────────────
  DEITAM provisioning complete.

  App user        : ${APP_USER}
  App directory   : ${APP_DIR}
  Public URL      : https://${APP_DOMAIN}
  DB              : ${DB_FLAVOUR} · ${DB_NAME} · user ${DB_USER}
  DB password     : (written to ${APP_DIR}/.env — read with: sudo grep DB_PASSWORD ${APP_DIR}/.env)
  Caddyfile       : /etc/caddy/Caddyfile
  Logs            : /var/log/deitam/  ·  /var/log/caddy/

  Services:
    systemctl status deitam-horizon deitam-reverb deitam-scheduler.timer
    systemctl status caddy php${PHP_VERSION}-fpm mysql redis-server

  Next steps:
    1. Point DNS A/AAAA records for ${APP_DOMAIN} (and any tenant subdomains
       under ${APP_PLATFORM_BASE_DOMAIN}) at this server's public IP.
    2. Within ~30s of DNS resolving, Caddy will auto-issue a Let's Encrypt cert.
    3. Create the first system admin:
         sudo -u ${APP_USER} bash -c "cd ${APP_DIR} && php artisan tinker"
       then in tinker:
         App\\Models\\User::create([
             'name'  => 'Root',
             'email' => 'you@${APP_DOMAIN#*.}',
             'password' => bcrypt('changeme'),
             'is_system_admin' => true,
         ]);
    4. For subsequent deploys, use deploy/deploy.sh (pull + migrate + cache + reload).

  ───────────────────────────────────────────────────────────────────
EOF
