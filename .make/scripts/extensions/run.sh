#!/usr/bin/env bash
# Extensions of the running instance, through the Module Loader of SuiteCRM:
# lists them, installs or uninstalls those of SLUGS. The same thing the
# buttons of Administration → Chargeur de module do (scripts/packages.php).
#
# @usage make extensions@list
#        make extensions@install SLUGS="pwa login-background"
#        make extensions@uninstall SLUGS="login-background"
set -euo pipefail
. vendor/david-grosskost/suitecrm-devkit/.make/scripts/lib.sh

command="$1"
load_env "extensions@$command"

container="${APP_SLUG:?APP_SLUG manquant dans .env}-app"
docker inspect "$container" >/dev/null 2>&1 \
  || die "extensions@$command : conteneur $container absent — lancer « make app@start »"

if [ "$command" != list ] && [ -z "${SLUGS:-}" ]; then
  die "extensions@$command : indiquer les extensions, par exemple SLUGS=\"pwa login-background\""
fi

# SLUGS holds slugs separated by spaces: split on purpose.
# shellcheck disable=SC2086
run docker exec -u www-data "$container" php /usr/local/share/suitecrm/packages.php "$command" ${SLUGS:-}
