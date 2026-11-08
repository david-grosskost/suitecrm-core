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
# Created once the container is ready (used by the healthcheck, hence by the
# cron container, which waits for it). Removed first: a restarted container is
# not ready before this script has run again.
READY=/run/suitecrm-ready
rm -f "$READY"

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

  # A data directory created by Docker on the server belongs to root: SuiteCRM
  # writes at the root of the application (.env.local at install).
  chown www-data:www-data "$APP"

  # Caches built from the previous version's code and labels: SuiteCRM
  # rebuilds them on the next request.
  # - cache/prod: SuiteCRM 8 (Symfony container, labels and metadata served to
  #   the interface). The image ships it empty, so it only holds runtime data.
  # - public/legacy/cache: language files of the legacy part.
  rm -rf "$APP/cache/prod"
  rm -rf "$APP/public/legacy/cache/jsLanguage"
  if [ -d "$APP/public/legacy/cache/modules" ]; then
    find "$APP/public/legacy/cache/modules" -path '*/language/*.lang.php' -delete
  fi

  # Written last: marks the sync as complete.
  cp -p "$SRC/.image-version" "$APP/.image-version"
fi

# Extensions chosen for this instance (EXTENSIONS, see compose.yaml), from
# those of the image: only does something when the list or the image changes.
suitecrm-extensions

# Local and demo: every email goes to Mailpit, set again at each start so that
# a change in the admin screens never lets a demo send real emails. Once
# installed only: on a new instance, suitecrm-install does it. Before READY:
# the cron container, which sends and fetches emails, starts after this.
if [ -n "${MAIL_SMTP_HOST:-}" ] && [ -f "$APP/public/legacy/config.php" ]; then
  runuser -u www-data -- php /usr/local/share/suitecrm/mail.php
fi

touch "$READY"

exec "$@"
