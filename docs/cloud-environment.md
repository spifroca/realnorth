# Cloud-Umgebung «realnorth» — verifizierte Konfiguration

Anlegen: claude.ai/code -> Wolken-Icon über dem Eingabefeld -> **Add cloud
environment**. (Keine Settings-Seite, keine direkte URL — nur dieser Selector.
Persönliche Umgebungen hängen am Account; org-weite legen Owner/Admins unter
claude.ai/admin-settings -> «Cloud environments» an.)

Diese Fassung ist gegen eine laufende Session geprüft; die Messwerte stehen
jeweils dabei.

## Name

    realnorth

## Network access

**Custom**, mit angehakter Option «Also include default list of common package
managers» (sonst fallen npm/Packagist/apt/GitHub weg).

Allowed domains:

    realnorth.ch
    *.realnorth.ch
    rlx1.loginserver.ch
    *.loginserver.ch

`rlx1.loginserver.ch` ist neu dazu — das ist der Plesk-Host der Seite
(gleiche IP 46.4.250.97 wie realnorth.ch).

**Wichtige Einschränkung, gemessen:** Der Egress-Proxy lässt CONNECT
praktisch nur auf Port 443 zu.

    https://realnorth.ch              -> 403 CONNECT tunnel failed  (ohne Freigabe)
    https://rlx1.loginserver.ch       -> 403 CONNECT tunnel failed  (ohne Freigabe)
    https://rlx1.loginserver.ch:8443  -> Connection reset by peer
    TCP 22                            -> dicht

Heisst: Mit der Freigabe oben wird die **Website** abrufbar (HTTP-Checks,
Playwright-Screenshots, Diff gegen Deployment). Das **Plesk-Panel auf 8443**
und **SSH/SFTP auf 22** bleiben aller Wahrscheinlichkeit nach zu — die
Freigabe wirkt auf Domains, nicht auf Ports. Panel-Arbeit bleibt Handarbeit
im Browser; der Code-Weg läuft über GitHub (siehe `plesk-deploy.md`).

## Environment variables (.env-Format)

    PROJECT=realnorth
    SITE_URL=https://realnorth.ch
    PLESK_PANEL_URL=https://rlx1.loginserver.ch:8443

Keine Secrets hier ablegen — die Werte liest jeder, der die Umgebung benutzt,
und es gibt (noch) keinen Secrets-Store. Kein `GH_TOKEN`: der GitHub-Proxy
authentisiert git von aussen, ohne Credential in der VM. Keine
Plesk-Zugangsdaten und keine DB-Passwörter.

## Setup script

Läuft als root auf Ubuntu 24.04, einmalig pro Cache-Generation, muss unter
~5 Minuten bleiben und mit 0 enden.

**Empfehlung: das Setup-Script leer lassen bzw. gar nicht befüllen.** Das
WordPress-Tooling steckt jetzt im Repo unter
`.claude/hooks/session-start.sh` (installiert wp-cli, setzt
`WP_CLI_ALLOW_ROOT`). Das ist der bessere Ort: es ist versioniert, läuft in
jeder Session auf jeder Umgebung und lokal genauso.

Faustregel: **Setup-Script = VM provisionieren, Hook = Projekt-Setup.**

Falls trotzdem etwas auf VM-Ebene gebraucht wird — hier die geprüften
Bausteine:

```bash
#!/bin/bash
set -u

# Optional: MariaDB-Client. Nutzen aktuell fraglich - die Projekt-DB liegt
# auf dem Plesk-Host und ist aus der Session nicht erreichbar (Port 3306
# ist zu). Nur sinnvoll fuer eine lokale Docker-MySQL zum Offline-Testen.
apt-get update -qq || true
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq mariadb-client || true

exit 0
```

Anmerkungen zu diesem Script:

* `gh` ist bewusst draussen. Die Session spricht mit GitHub über die
  GitHub-MCP-Tools, nicht über die CLI. (Falls doch gewünscht: `gh` 2.45.0
  liegt in `noble/universe`, `apt-get install gh` funktioniert.)
* `apt-get update` gibt Warnungen für zwei PPAs aus (`deadsnakes`,
  `ondrej/php` -> 403 vom Proxy). Exit-Code bleibt 0, die Ubuntu-Repos selbst
  gehen. Deshalb `|| true`.
* wp-cli-Download von `raw.githubusercontent.com` funktioniert (HTTP 200) —
  auch ohne Custom-Freigabe, das läuft über die Default-Package-Manager-Liste.

## Was auf der VM schon drin ist (gemessen, `check-tools`)

| Tool | Version |
|---|---|
| PHP | 8.4.19 CLI, mit `mysqli`, `pdo_mysql`, `curl`, `gd`, `mbstring`, `zip` |
| Composer | vorhanden (`/usr/local/bin/composer`) |
| Node | 22.22.2, npm 10.9.7, yarn 1.22.22, pnpm 10.33.0 |
| Python | 3.11.15, ruff/black/mypy/pytest |
| Docker | 29.3.1 |
| Postgres-Client | `psql` vorhanden |
| Redis-Client | `redis-cli` vorhanden |
| Chromedriver | 147 (Playwright/Chromium vorinstalliert) |

Nicht dabei und selbst nachzuinstallieren: **wp-cli** (macht der Hook),
**gh**, **MySQL/MariaDB-Client**.

## Danach

1. Umgebung im Selector wählen, **bevor** die Session startet.
   Aus dem Terminal: `/remote-env` setzt die Default-Umgebung für
   `claude --cloud`.
2. Erste Session gegen die neue Umgebung: `curl -I https://realnorth.ch`
   muss 200/301 liefern. Tut es das nicht, greift die Freigabe nicht.
3. `check-tools` zeigt die exakten Versionen auf der VM.
