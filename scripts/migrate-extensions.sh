#!/usr/bin/env bash
# One-off migration of an instance created before the extensions moved to the
# Module Loader (core 0.25): EXTENSIONS copied their files into
# public/legacy/custom/ and extensions/<Nom>/, and listed what it had written
# in .extensions/. Those files are taken out here, then the same extensions are
# installed again through the Module Loader, which from now on owns them.
#
# The extensions keep their data: their install scripts find what they created
# (a panel in a Studio layout, a scheduler) and leave it as it is.
#
# Does nothing once done, and nothing on an instance that never had
# EXTENSIONS. Run as root by suitecrm-entrypoint, before packages.php.
#
# @usage suitecrm-migrate-extensions
set -euo pipefail

APP=/var/www/html
STATE="$APP/.extensions"
LEGACY="$APP/public/legacy/custom"

[ -d "$STATE" ] || exit 0

# Directory of an extension in extensions/: its slug in PascalCase.
namespace_dir () { sed -E 's/(^|-)([a-z0-9])/\U\2/g' <<< "$1"; }

active=()
[ -f "$STATE/active" ] && mapfile -t active < <(grep -v '^$' "$STATE/active" || true)

echo "suitecrm : extensions reprises par le Chargeur de module (${active[*]:-aucune})"

# 1. Take out what the old activation had written, file by file, from its own
#    lists: nothing else in custom/ is touched.
for list in "$STATE"/*.files; do
  [ -e "$list" ] || continue
  slug="$(basename "$list" .files)"
  while IFS= read -r file; do
    [ -n "$file" ] || continue
    rm -f "$LEGACY/$file"
    dir="$(dirname "$file")"
    while [ "$dir" != . ] && rmdir "$LEGACY/$dir" 2>/dev/null; do
      dir="$(dirname "$dir")"
    done
  done < "$list"
  rm -rf "${APP:?}/extensions/$(namespace_dir "$slug")"
done

# 2. The Module Loader installs them again, from the packages of the image.
#    Done before the state is removed: a failed start tries again.
if [ -f "$APP/public/legacy/config.php" ] && [ "${#active[@]}" -gt 0 ]; then
  runuser -u www-data -- php /usr/local/share/suitecrm/packages.php install "${active[@]}"
fi

rm -rf "${STATE:?}"
rm -rf "$APP/cache/prod"
