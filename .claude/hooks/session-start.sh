#!/bin/bash
# SessionStart-Hook: stellt das WordPress-Tooling bereit, das in einer
# frischen Cloud-Session fehlt. Laeuft nur remote (Claude Code on the web).
set -uo pipefail

if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

# wp-cli: Theme-/i18n-Checks, Scaffolding, Doctor-Kommandos. Braucht keine
# DB-Verbindung fuer die Kommandos, die wir hier nutzen.
if [ ! -x /usr/local/bin/wp ]; then
  if curl -fsSL --max-time 120 -o /tmp/wp-cli.phar \
      https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar; then
    install -m 0755 /tmp/wp-cli.phar /usr/local/bin/wp || true
    rm -f /tmp/wp-cli.phar
  else
    echo "Hinweis: wp-cli-Download fehlgeschlagen - Session laeuft ohne wp." >&2
  fi
fi

# wp-cli laeuft in dieser VM als root; ohne das Flag verweigert es den Dienst.
if [ -n "${CLAUDE_ENV_FILE:-}" ]; then
  echo 'export WP_CLI_ALLOW_ROOT=1' >> "$CLAUDE_ENV_FILE"
fi

php -v 2>/dev/null | head -1
wp --allow-root --version 2>/dev/null || true

exit 0
