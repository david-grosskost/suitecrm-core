#!/usr/bin/env bash
# Lists the labels still in English: the reference code of the local image
# (French pack and lang/ complements included) against SuiteCRM's English
# files. Writes one file to translate per language file in .tools/i18n/fr_FR/,
# at the path to use in lang/ (see lang/README.md).
#
# @usage make i18n@missing
set -euo pipefail
. vendor/david-grosskost/suitecrm-devkit/.make/scripts/lib.sh

load_env i18n@missing

image=suitecrm-core:dev
docker image inspect "$image" >/dev/null 2>&1 \
  || die "i18n@missing : image $image absente — lancer « make app@start »"

out=.tools/i18n/fr_FR
rm -rf "$out"
mkdir -p "$out"

run docker run --rm --user "$(id -u):$(id -g)" --entrypoint php \
  -v "$(host_pwd):/app" -w /app "$image" \
  -d memory_limit=1G scripts/i18n-missing.php /usr/src/suitecrm/public/legacy "$out" fr_FR
