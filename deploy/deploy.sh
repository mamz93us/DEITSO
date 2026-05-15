#!/usr/bin/env bash
#
# DEITAM — ongoing deploy. Run on the VPS *after* provision-ubuntu-24.sh has
# bootstrapped the box. Pulls the latest code, installs deps, migrates,
# rebuilds caches, and reloads PHP-FPM / Horizon / Reverb.
#
# Run as the app user (the one provision-ubuntu-24.sh created — usually
# 'deitam'). Service restarts use sudo with NOPASSWD entries you set up
# during onboarding (see end of this file for the required sudoers snippet).
#
# Usage:
#   sudo -u deitam bash /var/www/deitam/deploy/deploy.sh [branch]
#

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/deitam}"
BRANCH="${1:-${GIT_BRANCH:-master}}"
PHP_VERSION="${PHP_VERSION:-8.3}"

log() { printf '\n\033[1;34m==>\033[0m %s\n' "$*"; }
cd "${APP_DIR}"

log "Enabling maintenance mode"
php artisan down --render="errors::503" --retry=15 || true
trap 'php artisan up || true' EXIT

log "Fetching ${BRANCH}"
git fetch --all --prune
git reset --hard "origin/${BRANCH}"

log "composer install (production)"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

log "npm ci + build"
npm ci --no-audit --no-fund
npm run build

log "Running migrations"
php artisan migrate --force

log "Rebuilding caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache 2>/dev/null || true
php artisan filament:optimize 2>/dev/null || php artisan filament:cache-components 2>/dev/null || true

log "Reloading PHP-FPM (drops OPcache)"
sudo /usr/bin/systemctl reload "php${PHP_VERSION}-fpm"

log "Restarting Horizon + Reverb"
# Horizon's --signal handler does a graceful, drain-then-restart cycle.
php artisan horizon:terminate || true
sudo /usr/bin/systemctl restart deitam-horizon deitam-reverb

log "Disabling maintenance mode"
php artisan up
trap - EXIT

log "Done. Live URL: $(php -r 'echo trim(file_get_contents(".env"));' | grep -m1 APP_URL | cut -d= -f2-)"

# ───────────────────────────────────────────────────────────────────
# One-time sudoers setup so this script can reload services without a
# password prompt. As root:
#
#   visudo -f /etc/sudoers.d/deitam-deploy
#   # paste:
#   deitam ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.3-fpm, \
#                               /usr/bin/systemctl restart deitam-horizon, \
#                               /usr/bin/systemctl restart deitam-reverb
#
#   chmod 0440 /etc/sudoers.d/deitam-deploy
# ───────────────────────────────────────────────────────────────────
