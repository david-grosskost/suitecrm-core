#!/usr/bin/env bash
# Activates the extensions listed in EXTENSIONS (slugs separated by commas or
# spaces), from the catalogue of the image (/usr/local/share/suitecrm/extensions,
# built from composer.json by scripts/extensions-catalog.php). See the
# architecture decisions, "Format des extensions".
#
# - SuiteCRM 8 layer: backend/ and config/ go to extensions/<slug>/, which
#   Symfony loads by itself;
# - legacy layer: legacy/… goes to public/legacy/custom/…, file by file; the
#   files written are listed in .extensions/<slug>.files, so that an extension
#   that is turned off or changed leaves nothing behind. A file already there
#   (the project's, or another extension's) is never overwritten: the start
#   fails;
# - when the set changes (another list, or another version of an extension):
#   Quick Repair (scripts/repair.php, once SuiteCRM is installed), and the
#   SuiteCRM 8 cache is cleared.
#
# Run as root by suitecrm-entrypoint at each start, before the cron container
# starts. Unchanged extensions: nothing is written. An unknown slug stops the
# start, with the list of the catalogue.
#
# @usage EXTENSIONS="correctifs-suitecrm siret-validation" suitecrm-extensions
set -euo pipefail

CATALOG=/usr/local/share/suitecrm/extensions
APP=/var/www/html
STATE="$APP/.extensions"
LEGACY="$APP/public/legacy/custom"

die () {
  echo "suitecrm-extensions : $*" >&2
  exit 1
}

read -ra wanted <<< "${EXTENSIONS//,/ }"
mapfile -t wanted < <(printf '%s\n' "${wanted[@]}" | grep -v '^$' | sort -u)

catalog="$(find "$CATALOG" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null | sort | paste -sd ' ' || true)"
for slug in "${wanted[@]}"; do
  [[ $slug =~ ^[a-z][a-z0-9]*(-[a-z0-9]+)*$ ]] || die "nom d'extension invalide « $slug »"
  [ -d "$CATALOG/$slug" ] || die "extension « $slug » absente de l'image (catalogue : ${catalog:-vide})"
done

# What should be active: the slugs, and the content of their files.
fingerprint="$(
  for slug in "${wanted[@]}"; do
    echo "$slug"
    (cd "$CATALOG/$slug" && find . -type f -print0 | sort -z | xargs -0 -r sha256sum)
  done | sha256sum | cut -c1-16
)"
if [ "$fingerprint" = "$(cat "$STATE/fingerprint" 2>/dev/null || true)" ]; then
  echo "suitecrm-extensions : à jour (${wanted[*]:-aucune})"
  exit 0
fi
echo "suitecrm-extensions : activation (${wanted[*]:-aucune extension})"

mkdir -p "$STATE"

# 1. Take out every extension of the previous start: files listed, then the
#    SuiteCRM 8 directory. Those still wanted come back just below.
for list in "$STATE"/*.files; do
  [ -e "$list" ] || continue
  slug="$(basename "$list" .files)"
  while IFS= read -r file; do
    rm -f "$LEGACY/$file"
  done < "$list"
  rm -rf "${APP:?}/extensions/$slug"
  rm -f "$list"
done

# 2. Put in the wanted ones.
for slug in "${wanted[@]}"; do
  src="$CATALOG/$slug"

  for dir in backend config; do
    if [ -d "$src/$dir" ]; then
      mkdir -p "$APP/extensions/$slug"
      cp -a "$src/$dir" "$APP/extensions/$slug/"
    fi
  done

  files=()
  if [ -d "$src/legacy" ]; then
    mapfile -t files < <(cd "$src/legacy" && find . -type f -printf '%P\n' | sort)
  fi
  # Every file checked before any is written or listed: a refused start never
  # leaves a list that would delete, on the next start, a file not ours.
  for file in "${files[@]}"; do
    [ ! -e "$LEGACY/$file" ] \
      || die "public/legacy/custom/$file existe déjà (fichier du projet ou d'une autre extension) : « $slug » ne l'écrase pas"
  done
  printf '%s\n' "${files[@]}" | grep -v '^$' > "$STATE/$slug.files" || true
  for file in "${files[@]}"; do
    mkdir -p "$(dirname "$LEGACY/$file")"
    cp -a "$src/legacy/$file" "$LEGACY/$file"
  done
done

chown -R www-data:www-data "$APP/extensions" "$STATE"
[ -d "$LEGACY" ] && chown -R www-data:www-data "$LEGACY"

# 3. SuiteCRM takes them into account: Quick Repair (installed only: at a
#    first install, suitecrm-install runs it), then the SuiteCRM 8 cache.
if [ -f "$APP/public/legacy/config.php" ]; then
  runuser -u www-data -- php /usr/local/share/suitecrm/repair.php
fi
rm -rf "$APP/cache/prod"

printf '%s\n' "${wanted[@]}" | grep -v '^$' > "$STATE/active" || true
# Written last: marks the activation as complete.
echo "$fingerprint" > "$STATE/fingerprint"
