#!/bin/bash
# Alle Tests unter tests/ ausführen. Brauchen kein WordPress.
set -uo pipefail

cd "$(dirname "$0")/.." || exit 1

shopt -s nullglob
files=( tests/*.php )

if [ "${#files[@]}" -eq 0 ]; then
  echo "test: keine Tests gefunden."
  exit 0
fi

fail=0
for f in "${files[@]}"; do
  echo "--- $f"
  php "$f" || fail=1
done

exit "$fail"
