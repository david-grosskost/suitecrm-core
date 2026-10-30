#!/usr/bin/env bash
# Syncs the SuiteCRM code from the image into the app volume, then runs the given command.
#
# The volume holds the whole application, because SuiteCRM writes into itself
# (config, Studio, Module Builder, uploads). On a new image version, the code is
# copied over the volume without --delete: files added by SuiteCRM are kept.
# Once installed, the files SuiteCRM owns are never overwritten.
#
# @usage suitecrm-entrypoint apache2-foreground
set -euo pipefail

SRC=/usr/src/suitecrm
APP=/var/www/html

image_version="$(cat "$SRC/.image-version")"
app_version="$(cat "$APP/.image-version" 2>/dev/null || true)"

if [ "$image_version" != "$app_version" ]; then
  echo "suitecrm : code ${app_version:-absent} → $image_version"

  excludes=(--exclude=/.image-version)
  if [ -f "$APP/public/legacy/config.php" ]; then
    excludes+=(
      --exclude=/.env.local
      --exclude=/public/legacy/config.php
      --exclude=/public/legacy/config_override.php
      --exclude=/public/legacy/custom/
      --exclude=/public/legacy/upload/
    )
  fi

  rsync -a "${excludes[@]}" "$SRC/" "$APP/"

  # Written last: marks the sync as complete (used by the healthcheck).
  cp -p "$SRC/.image-version" "$APP/.image-version"
fi

exec "$@"
