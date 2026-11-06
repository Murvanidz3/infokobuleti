#!/usr/bin/env bash
# Activates a release unpacked into <root>/releases/<id>: links the shared .env and storage,
# installs dependencies when the package has none, runs migrations, builds the caches and then
# switches <root>/current in one atomic step. The old release keeps serving until the switch.
# If the site does not answer afterwards, the previous release is put back.
#
# Migrations run while the old release is still live, so they must not break it (add columns and
# tables first, remove old ones in a later release).
set -euo pipefail
ROOT="$1"
RELEASE="$2"
log() { echo "[deploy] $*"; }
cd "$RELEASE"

ln -sfn "$ROOT/shared/.env" .env
rm -rf storage
ln -sfn "$ROOT/shared/storage" storage
mkdir -p bootstrap/cache

if [ ! -f vendor/autoload.php ]; then
  log "installing PHP packages"
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress 2>&1 | tail -n 3
fi
[ -f public/build/manifest.json ] || { log "public/build is missing: build the CSS/JS first"; exit 1; }

php artisan storage:link --force > /dev/null 2>&1 || true
php artisan migrate --force --no-interaction
php artisan optimize > /dev/null
php artisan sitemap:generate > /dev/null 2>&1 || log "sitemap not generated"

PREVIOUS=$(readlink -f "$ROOT/current" 2>/dev/null || true)
switch_to() {
  ln -sfn "$1" "$ROOT/current.next"
  mv -Tf "$ROOT/current.next" "$ROOT/current"
  sudo -n /usr/bin/systemctl reload php8.4-fpm
  php "$ROOT/current/artisan" horizon:terminate > /dev/null 2>&1 || true
}
switch_to "$RELEASE"

URL=$(grep -E '^APP_URL=' "$ROOT/shared/.env" | cut -d= -f2- | tr -d '"')
HOST=${URL#*://}
HOST=${HOST%%/*}
CODE=$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 --resolve "$HOST:443:127.0.0.1" "https://$HOST/up" || echo 000)
# 200 = healthy; 401 = healthy behind the staging password.
if [ "$CODE" != "200" ] && [ "$CODE" != "401" ]; then
  log "health check failed (HTTP $CODE)"
  if [ -n "$PREVIOUS" ] && [ -d "$PREVIOUS" ] && [ "$PREVIOUS" != "$RELEASE" ]; then
    switch_to "$PREVIOUS"
    log "rolled back to $(basename "$PREVIOUS")"
  fi
  exit 1
fi

# Keep the five newest releases.
ls -1d "$ROOT"/releases/*/ | sort -r | tail -n +6 | xargs -r rm -rf
log "live: $(basename "$RELEASE") on $HOST (HTTP $CODE)"
