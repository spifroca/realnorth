#!/bin/bash
# Syntax-Check aller PHP-Dateien im Repo. Vor jedem Commit laufen lassen.
# Ein Syntaxfehler in einem Theme-File wirft die Live-Seite in den WSoD,
# und ohne Shell-Zugriff auf dem Server ist die Korrektur nur ueber einen
# weiteren Push oder den Plesk File Manager moeglich.
set -uo pipefail

cd "$(dirname "$0")/.." || exit 1

mapfile -d '' files < <(
  find . -type f -name '*.php' \
    -not -path './vendor/*' \
    -not -path './node_modules/*' \
    -not -path './.git/*' \
    -print0
)

if [ "${#files[@]}" -eq 0 ]; then
  echo "php-lint: keine PHP-Dateien gefunden - nichts zu pruefen."
  exit 0
fi

fail=0
for f in "${files[@]}"; do
  if ! out=$(php -l "$f" 2>&1); then
    echo "FEHLER  $f"
    echo "$out" | sed 's/^/        /'
    fail=1
  fi
done

if [ "$fail" -eq 0 ]; then
  echo "php-lint: ${#files[@]} Datei(en) ok."
else
  echo "php-lint: Syntaxfehler gefunden - NICHT pushen."
fi
exit "$fail"
