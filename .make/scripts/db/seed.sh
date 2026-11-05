#!/usr/bin/env bash
# Loads the French demo data into the running instance (database/seeders/), as
# www-data in the app container. Safe to run again: records are updated,
# never duplicated. Never in prod: demo data has nothing to do there.
#
# @usage make db@seed [SEEDERS="core/accounts core/contacts"]
set -euo pipefail
. vendor/david-grosskost/suitecrm-devkit/.make/scripts/lib.sh

load_env db@seed
[ "$APP_ENV" != prod ] || die "db@seed : jamais en prod (APP_ENV=prod)"

container="${APP_SLUG:?APP_SLUG manquant dans .env}-app"
docker inspect "$container" >/dev/null 2>&1 \
  || die "db@seed : conteneur $container absent — lancer « make app@start »"

# SEEDERS holds seeder names separated by spaces: split on purpose.
# shellcheck disable=SC2086
run docker exec -u www-data "$container" php /usr/local/share/suitecrm/database/seeders/seed.php ${SEEDERS:-}
