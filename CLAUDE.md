# realnorth.ch — Arbeitsregeln für dieses Repo

## Was das hier ist

Code der WordPress-Website **realnorth.ch**, gehostet auf einem Plesk-Server
(`rlx1.loginserver.ch`, Panel auf Port 8443). Das Repo enthält **nur den
Teil, den wir pflegen** (Theme bzw. Child-Theme) — nicht den WordPress-Core,
nicht `wp-config.php`, nicht `wp-content/uploads/`.

Repo-Root entspricht dem Theme-Root auf dem Server:
`httpdocs/wp-content/themes/<slug>/`.

## Wie Code live geht

Push nach GitHub -> **Plesk zieht** (Git-Integration, Auto-Deploy per
Webhook). Details und Einrichtung: `docs/plesk-deploy.md`.

Daraus folgen drei harte Regeln:

1. **Kein Build auf dem Server.** Das Abo hat keinen Shell-Zugriff, also
   keine Plesk-Deploy-Actions, kein `composer install`, kein `npm ci`. Was
   live wirken soll, muss fertig im Repo liegen — kompiliertes CSS/JS wird
   eingecheckt, nicht ignoriert.
2. **Nie Dateien im Plesk File Manager bearbeiten**, die im Repo liegen. Der
   nächste Deploy überschreibt sie kommentarlos. Ausnahme: Notfall-Rollback.
3. **`bin/php-lint.sh` muss grün sein, bevor gepusht wird.** Ein
   PHP-Syntaxfehler bedeutet weisser Screen auf der Live-Seite, und ohne
   Shell gibt es dort kein `php -l` zum Nachsehen.

## Netzwerk in Cloud-Sessions

`realnorth.ch`, das Plesk-Panel und SSH sind aus einer Standard-Session
**nicht erreichbar** (Egress-Proxy: `403 CONNECT tunnel failed`; Port 8443
wird resettet). Also:

* Keine Live-Checks, keine Screenshots der echten Seite, kein SFTP-Deploy
  annehmen, solange die Cloud-Umgebung «realnorth» nicht aktiv ist
  (`docs/cloud-environment.md`).
* Der Ist-Zustand der Seite kommt über die Checkliste in
  `docs/ist-zustand-erfassen.md` ins Repo, nicht über einen Abruf.
* Was funktioniert: GitHub (git push, MCP-Tools), npm/Packagist/PyPI,
  `raw.githubusercontent.com`.

## WordPress-Konventionen

* Ausgaben escapen: `esc_html()`, `esc_attr()`, `esc_url()`,
  `wp_kses_post()`. Kein ungeprüftes `echo` von Nutzer- oder DB-Daten.
* Eingaben absichern: Nonces bei Formularen/AJAX, `current_user_can()` vor
  jeder Änderung, `sanitize_*()` beim Speichern.
* Datenbank nur über WordPress-APIs (`WP_Query`, `get_posts()`,
  `get_option()`), `$wpdb` nur mit `$wpdb->prepare()`.
* Assets über `wp_enqueue_style()` / `wp_enqueue_script()` mit
  Versionsstring registrieren — keine `<link>`/`<script>`-Tags direkt im
  Template (sonst greift kein Cache-Busting).
* Text-Domain konsistent verwenden, Strings über `__()` / `_e()`.
* Keine hartcodierten URLs oder Pfade: `get_template_directory_uri()`,
  `home_url()`, `get_stylesheet_directory()`.
* Kein Ändern von Dateien ausserhalb des Theme-Roots — das Repo deployt nur
  hierhin.

## Vor jedem Commit

    ./bin/php-lint.sh

Commits klein und thematisch halten. Ein Deploy = ein Push; wenn eine
Änderung riskant ist, vorher sagen, wie der Rollback aussieht
(`docs/plesk-deploy.md`, Abschnitt Rollback).

## Offene Punkte

Solange `docs/ist-zustand-erfassen.md` nicht abgearbeitet ist, sind
unbekannt: aktives Theme und Theme-Slug, ob ein Page-Builder im Spiel ist,
WordPress- und PHP-Version auf dem Server, Plugin-Bestand. Bis dahin keine
Annahmen darüber treffen.
