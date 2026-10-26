#!/usr/bin/env bash
# Deploy entry point on the VPS, installed as /usr/local/bin/infokobuleti-deploy (runs as "deploy").
#
#   infokobuleti-deploy staging|production      < release.tgz   unpack, activate, switch (no downtime)
#   infokobuleti-deploy rollback staging|production              back to the previous release
#   infokobuleti-deploy releases staging|production              list releases, show the live one
#
# GitHub Actions calls it through an SSH key that may run nothing else (forced command): the
# requested action then arrives in SSH_ORIGINAL_COMMAND.
set -euo pipefail

if [ "$(id -un)" != "deploy" ]; then
  exec sudo -u deploy -H SSH_ORIGINAL_COMMAND="${SSH_ORIGINAL_COMMAND:-}" "$0" "$@"
fi

request="${SSH_ORIGINAL_COMMAND:-$*}"
if ! [[ "$request" =~ ^(staging|production|rollback\ (staging|production)|releases\ (staging|production))$ ]]; then
  echo "usage: infokobuleti-deploy staging|production | rollback <env> | releases <env>" >&2
  exit 2
fi
read -r action target <<< "$request"

root_of() {
  case "$1" in
    staging) echo /var/www/infokobuleti-staging ;;
    production) echo /var/www/infokobuleti-production ;;
  esac
}

case "$action" in
  staging|production)
    ROOT=$(root_of "$action")
    RELEASE="$ROOT/releases/$(date +%Y%m%d-%H%M%S)"
    mkdir -p "$RELEASE"
    if ! tar -xzf - -C "$RELEASE"; then
      rm -rf "$RELEASE"
      echo "[deploy] could not unpack the release" >&2
      exit 1
    fi
    exec bash "$RELEASE/deploy/activate.sh" "$ROOT" "$RELEASE"
    ;;
  rollback)
    ROOT=$(root_of "$target")
    exec bash "$(readlink -f "$ROOT/current")/deploy/rollback.sh" "$ROOT"
    ;;
  releases)
    ROOT=$(root_of "$target")
    ls -1r "$ROOT/releases"
    echo "live: $(basename "$(readlink -f "$ROOT/current")")"
    ;;
esac
