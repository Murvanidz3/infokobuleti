#!/usr/bin/env bash
# Puts the release before the live one back (code only: migrations are not reversed).
set -euo pipefail
ROOT="$1"
CURRENT=$(basename "$(readlink -f "$ROOT/current")")
PREVIOUS=$(ls -1 "$ROOT/releases" | sort | awk -v current="$CURRENT" '$0 < current' | tail -n 1)

if [ -z "$PREVIOUS" ]; then
  echo "[deploy] there is no release older than $CURRENT" >&2
  exit 1
fi

cd "$ROOT/releases/$PREVIOUS"
php artisan optimize > /dev/null
ln -sfn "$ROOT/releases/$PREVIOUS" "$ROOT/current.next"
mv -Tf "$ROOT/current.next" "$ROOT/current"
sudo -n /usr/bin/systemctl reload php8.4-fpm
php "$ROOT/current/artisan" horizon:terminate > /dev/null 2>&1 || true
echo "[deploy] live: $PREVIOUS (was $CURRENT)"
