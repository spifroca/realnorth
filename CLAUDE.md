# realnorth.ch — Arbeitsregeln für dieses Repo

## Was das hier ist

Dieses Repo **ist das Plugin `realnorth-custom`** für die WordPress-Seite
**realnorth.ch**. Repo-Root entspricht auf dem Server:

    /httpdocs/realnorth/wordpress/wp-content/plugins/realnorth-custom/

Hier liegen unsere Anpassungen — eigenes CSS, Hooks, Funktionen,
Shortcodes. Nicht hier: WordPress-Core, das Theme, Fremd-Plugins,
`wp-config.php`, Uploads, Datenbank.

## Die Seite in einem Absatz

WordPress 7.1 auf Plesk (`rlx1.loginserver.ch`), PHP 8.5.9, nginx 1.30.4,
MariaDB 10.11. Aktives Theme ist **PopularFX** (Fremd-Theme von Pagelayer),
gebaut wird mit dem Page-Builder **Pagelayer**. Details und offene Risiken:
`docs/ist-zustand.md`.

Daraus die wichtigste Arbeitsteilung:

* **Inhalte, Layouts, Seitenaufbau** liegen in der **Datenbank** und werden
  im wp-admin mit Pagelayer bearbeitet. Git kann daran nichts ändern — nie
  behaupten, ein Deploy würde ein Layout anpassen.
* **Code** (CSS, Hooks, Funktionen) gehört hierher ins Plugin.
* **Theme-Dateien nicht anfassen.** PopularFX ist fremd; ein Update
  überschreibt Änderungen. Wenn Templates überschrieben werden müssen,
  vorher über ein Child-Theme reden (`docs/ist-zustand.md` erklärt, warum
  das nicht gratis ist).

## Wie Code live geht

Push nach GitHub -> **Plesk zieht** (Git-Integration, Auto-Deploy per
Webhook). Einrichtung und Rollback: `docs/plesk-deploy.md`.

Der Weg dorthin ist automatisiert: ein Push auf `claude/**` startet GitHub
Actions (Lint + Tests). Ist der Lauf grün und die Repository-Variable
`AUTO_PROMOTE` auf `true`, schiebt der Workflow den Stand nach `main`, und
Plesk zieht ihn. Rot heisst: `main` bleibt stehen, nichts geht live.
Details und Notausschalter: `docs/automatischer-deploy.md`.

Konsequenz für die Arbeitsweise: **jeder Push kann live gehen.** Also
kleine Commits, Lint und Tests vorher lokal laufen lassen, und bei jeder
Änderung an der Ausgabe einen Testfall dazu — die Tests sind ab jetzt das
Einzige, was zwischen einem Fehler und der Live-Seite steht.

Drei harte Regeln:

1. **Kein Build auf dem Server.** Kein Shell-Zugriff, also keine
   Deploy-Actions, kein `composer install`, kein `npm ci`. Was live wirken
   soll, muss fertig im Repo liegen — kompiliertes CSS/JS wird eingecheckt.
2. **Nie Dateien im Plesk File Manager bearbeiten**, die im Repo liegen.
   Der nächste Deploy überschreibt sie kommentarlos.
3. **`bin/php-lint.sh` muss grün sein, bevor gepusht wird.** Ein
   Syntaxfehler bedeutet weisser Screen live, und ohne Shell gibt es dort
   kein `php -l`. Der Lint läuft hier auf PHP 8.4, der Server auf 8.5.9 —
   bei Version-spezifischer Syntax genauer hinschauen.

Notausschalter bei Problemen: wp-admin -> Plugins -> **realnorth Custom
deaktivieren**. Deshalb ein normales Plugin und kein mu-plugin.

## Netzwerk in Cloud-Sessions

`realnorth.ch`, das Plesk-Panel und SSH sind aus einer Standard-Session
**nicht erreichbar** (Egress-Proxy: `403 CONNECT tunnel failed`; Port 8443
wird resettet). Also:

* Keine Live-Checks, keine Screenshots der echten Seite, kein SFTP-Deploy
  annehmen, solange die Cloud-Umgebung «realnorth» nicht aktiv ist
  (`docs/cloud-environment.md`). Selbst dann bleibt das Panel auf 8443 zu.
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
  Versionsstring registrieren. Im Plugin dient `filemtime()` als Version —
  ohne Build-Schritt ist das das einzige verlässliche Cache-Busting.
* Priorität 20 bei `wp_enqueue_scripts`, damit unser CSS nach Theme und
  Pagelayer kommt. Dann reicht normale Spezifität statt `!important`.
* Text-Domain `realnorth`, Strings über `__()` / `_e()`.
* Keine hartcodierten URLs oder Pfade: `plugins_url()`,
  `plugin_dir_path()`, `home_url()`.
* Namespace `RealNorth`, `declare( strict_types=1 )`, `ABSPATH`-Guard in
  jeder PHP-Datei.

## Vor jedem Commit

    ./bin/php-lint.sh
    ./bin/test.sh

Commits klein und thematisch halten. Ein Deploy = ein Push; wenn eine
Änderung riskant ist, vorher sagen, wie der Rollback aussieht
(`docs/plesk-deploy.md`, Abschnitt Rollback).

## Textkorrekturen im Frontend

`includes/content-cleanup.php` entfernt einzelne Begriffe bei der Ausgabe
(`the_content`, `widget_text`), weil die Inhalte in der Pagelayer-Datenbank
liegen und nicht im Repo. Das ist eine Notlösung mit Ansage:

* Sie wirkt nur im Frontend — im wp-admin steht der Text weiterhin da.
* Sobald der Text in Pagelayer korrigiert ist, gehört der Eintrag hier
  wieder heraus, sonst bleibt eine Regel stehen, die niemand versteht.
* Jede Änderung an der Liste braucht einen Testfall in
  `tests/content-cleanup.php`, inklusive Gegenprobe, dass nicht zu viel
  entfernt wird.

## Offene Punkte

* Update-Rückstand bei Pagelayer und WPForms — nicht blind updaten,
  Begründung in `docs/ist-zustand.md`.
* Löschverhalten des Plesk-Deploys ist ungetestet
  (`docs/plesk-deploy.md`, Schritt 6).
* Repo ist öffentlich; für ein Kundenprojekt eher privat + Deploy-Key.
