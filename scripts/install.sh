#!/usr/bin/env bash
# Installs SuiteCRM in the app volume, once: does nothing if already installed.
# Runs inside the app container as www-data; settings come from the environment.
#
# @usage docker exec -u www-data -e DB_NAME -e DB_USER -e DB_PASSWORD -e APP_HOST \
#          -e ADMIN_USER -e ADMIN_PASSWORD <container> suitecrm-install
set -euo pipefail
cd /var/www/html

if [ -f public/legacy/config.php ]; then
  echo "  SuiteCRM déjà installé : rien à faire."
  exit 0
fi

for var in DB_NAME DB_USER DB_PASSWORD APP_HOST ADMIN_USER ADMIN_PASSWORD; do
  [ -n "${!var:-}" ] || { echo "suitecrm-install : $var manquant" >&2; exit 1; }
done

./bin/console suitecrm:app:install -n \
  -H db -Z 3306 -N "$DB_NAME" -U "$DB_USER" -P "$DB_PASSWORD" \
  -u "$ADMIN_USER" -p "$ADMIN_PASSWORD" \
  -S "https://$APP_HOST" -d no -W true

# The silent-install config holds the passwords in clear.
rm -f public/legacy/config_si.php

# Shared base configuration (French defaults), everything but its "<?php" line.
# A closing "?>" would turn the appended lines into plain text: remove it first.
override=public/legacy/config_override.php
sed -i '/^?>[[:space:]]*$/d' "$override"
echo >> "$override"
tail -n +2 /usr/local/share/suitecrm/config_override.php >> "$override"
echo "  Configuration de base appliquée (français par défaut)."

# The admin was created before the base configuration: align its preferences.
php /usr/local/share/suitecrm/post-install.php
