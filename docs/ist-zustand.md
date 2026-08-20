# Ist-Zustand realnorth.ch

Erfasst am 2026-08-20 aus dem WordPress-Site-Health-Bericht
(«Website-Zustand -> Informationen»). Aktualisieren: siehe
`ist-zustand-erfassen.md`.

## Stack

| | |
|---|---|
| WordPress | 7.1, Sprache `de_DE`, Single-Site |
| PHP | 8.5.9, `fpm-fcgi`, memory_limit 256M, Opcache **nicht aktiv** |
| Webserver | nginx 1.30.4 (**kein Apache — `.htaccess` wirkt nicht**) |
| Datenbank | MariaDB 10.11.18, 24,9 MB |
| Permalinks | `/%postname%/`, HTTPS aktiv, Seite öffentlich indexierbar |
| Grösse | 866 MB total, davon 206 MB Uploads |

## Pfade auf dem Server

    WordPress-Root   /var/www/vhosts/realnorth.ch/httpdocs/realnorth/wordpress
    wp-content       .../wordpress/wp-content
    Themes           .../wordpress/wp-content/themes
    Plugins          .../wordpress/wp-content/plugins

Relativ zum Abo-Root (das ist der Bezugspunkt für Plesk-Pfadfelder):

    /httpdocs/realnorth/wordpress

`wordpress`, `wp-content`, `uploads`, `plugins` und `themes` sind alle
beschreibbar.

## Theme

**PopularFX 1.2.5** (Slug `popularfx`, Autor Pagelayer), kein Parent-Theme.
Sieben inaktive Standard-Themes (Twenty Nineteen bis Twenty Twenty-Five)
liegen daneben.

PopularFX ist ein **Fremd-Theme**. Es gehört deshalb nicht ins Repo:
ein Update im wp-admin würde unsere Änderungen überschreiben, und
umgekehrt würde unser Deploy ein Update zurückdrehen.

## Plugins (aktiv)

| Plugin | Installiert | Aktuell |
|---|---|---|
| PageLayer | 1.8.8 | 2.1.8 |
| Pagelayer Pro | 1.6.3 | 2.1.8 |
| PopularFX Website Templates | 1.2.4 | 1.3.1 |
| UltraEmbed (Advanced Iframe) | 1.0.3 | — |
| WPForms | 1.6.8.1 | deutlich neuer |

Inaktiv: Akismet 5.3.3 (aktuell 5.7.2), Hello Dolly, WPForms Lite.
Automatische Updates sind überall deaktiviert.

WPForms hat 2 Formulare und **78 Einträge** in der Datenbank
(`wpfq_wpforms_entries`). Lizenzstatus: `Disabled`.

## Was das für unsere Arbeit bedeutet

**Die Seite ist mit einem Page-Builder gebaut.** Pagelayer speichert
Layouts und Inhalte in der Datenbank, nicht in Theme-Dateien. Daraus
folgt die Arbeitsteilung:

* **Inhalte, Layouts, Seitenstruktur** -> im wp-admin mit Pagelayer.
  Dafür ist Git der falsche Ort, und ein Deploy ändert daran nichts.
* **Code** — eigenes CSS, Hooks, Funktionen, Shortcodes -> in diesem Repo,
  als Plugin `realnorth-custom`. Kein Child-Theme (siehe unten).

### Warum Plugin und nicht Child-Theme

Ein Child-Theme von PopularFX wäre der klassische Weg, hat hier aber zwei
Nachteile:

1. **Theme-Wechsel setzt Customizer-Einstellungen zurück.** WordPress legt
   sie pro Theme ab (`theme_mods_popularfx`). Nach dem Umschalten auf ein
   Child-Theme sind Logo, Farben, Menü-Positionen und Widget-Zuordnungen
   erst einmal leer und müssen neu gesetzt werden.
2. Für CSS und Hooks braucht es kein Theme. Ein Plugin funktioniert
   unabhängig davon, welches Theme aktiv ist, und übersteht den
   PopularFX-Update-Sprung, der noch aussteht.

Ein Child-Theme kommt erst dazu, wenn wir wirklich Template-Dateien
überschreiben müssen. Dann bekommt es ein eigenes Plesk-Git-Repository
mit eigenem Pfad — und vorher wird `theme_mods_popularfx` gesichert.

## Offene Risiken

* **Update-Rückstand.** PageLayer 1.8.8 -> 2.1.8 ist ein
  Major-Version-Sprung, WPForms steht auf dem Stand von August 2021, und
  das auf PHP 8.5. Alte Plugins sind der häufigste Einbruchsweg bei
  WordPress. Gleichzeitig ist ein Blind-Update bei einem
  Page-Builder-Sprung dieser Grösse riskant — Layouts können brechen.
  Vorgehen: Klon über das Plesk WordPress Toolkit anlegen, dort updaten,
  Seiten durchklicken, erst dann live. Nicht ohne Backup.
* **Pagelayer Pro ohne Lizenz** (`license_status: Disabled`). Ohne
  aktive Lizenz gibt es keine Updates, also auch keine Sicherheitsfixes.
  Kaufmännisch klären, bevor man den Update-Sprung plant.
* **Kein Opcache.** Kein Cache-Problem nach Deploys, kostet aber Leistung.
* **`maintenance.php`-Drop-in vorhanden** in `wp-content`. Bestimmt nur das
  Aussehen der Wartungsseite während Updates; vor dem ersten Update
  einmal anschauen, damit klar ist, was Besucher dann sehen.
* **`.htaccess` ist wirkungslos**, weil nginx ohne Apache läuft.
  Zugriffsregeln gehören in Plesk unter «Apache & nginx Settings» ->
  «Additional nginx directives».
