#!/usr/bin/env bash
# Runs the SuiteCRM schedulers every minute: workflows, reminders, inbound e-mail…
# Uses "bin/console schedulers:run", the command SuiteCRM 8 asks for in
# Administration > Schedulers (not the SuiteCRM 7 legacy cron.php).
# Shares the app volume; does nothing until SuiteCRM is installed (no config.php yet).
# Stops at once on docker stop, instead of waiting for the end of a sleep.
#
# @usage suitecrm-cron (as www-data, entrypoint of the cron service)
set -uo pipefail

trap 'exit 0' TERM INT

cd /var/www/html || exit 1
echo "suitecrm-cron : démarré, une exécution par minute"

while true; do
  if [ -f public/legacy/config.php ]; then
    php bin/console schedulers:run || echo "suitecrm-cron : schedulers:run a échoué (code $?)" >&2
  fi
  sleep 60 &
  wait $!
done
