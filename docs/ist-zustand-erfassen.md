# Ist-Zustand von realnorth.ch erfassen

Solange das Repo leer ist, kenne ich die Seite nicht — und ich komme aus der
Session nicht an sie heran (Egress gesperrt, siehe `plesk-deploy.md`). Damit
wir mit echten Dateien statt Annahmen arbeiten, brauche ich einmal die
folgenden vier Dinge. Alles ohne Shell, alles im Browser machbar.

## 1. Website-Zustand aus WordPress (liefert 90 % der Antworten)

wp-admin -> **Werkzeuge** -> **Website-Zustand** -> Reiter **Infos** ->
Button **«Website-Informationen in die Zwischenablage kopieren»** -> hier
einfügen (oder als `.txt` ins Repo legen).

Darin steckt: WordPress-Version, aktives Theme inkl. Parent-Theme, alle
aktiven und inaktiven Plugins mit Versionen, PHP- und MySQL-Version,
PHP-Limits, Serverpfade, aktive Konstanten.

> **Vorher durchsehen und Zugangsdaten entfernen.** Der Bericht enthält
> normalerweise keine Passwörter, aber er enthält Pfade und Versionen. Keine
> DB-Passwörter, API-Keys oder `wp-config.php`-Inhalte hier einfügen — auch
> nicht ins Repo.

## 2. Aktives Theme als Zip

Plesk -> **File Manager** -> `httpdocs/wp-content/themes/` ->
den Ordner des aktiven Themes markieren -> **Herunterladen**.

Das ist gleichzeitig das Backup aus Schritt 0 der Deploy-Anleitung.

## 3. Wie wird die Seite inhaltlich gepflegt?

Kurz beantworten, das entscheidet, *wo* wir überhaupt sinnvoll arbeiten:

* Page-Builder im Einsatz (Elementor, WPBakery, Divi, Bricks …)? Wenn ja:
  Layouts liegen in der Datenbank, nicht im Theme — Code-Deploys betreffen
  dann nur Kleinteile (Funktionen, CSS-Feinschliff, Templates).
* Gutenberg/Block-Editor mit Standard-Theme?
* Oder ein handgebautes Custom-Theme (dann ist Git der richtige Ort für alles)?

## 4. Struktur von httpdocs

Plesk -> File Manager -> `httpdocs` -> Screenshot oder Dateiliste.
Interessant ist nur: liegt WordPress direkt in `httpdocs`, oder in einem
Unterordner; gibt es zusätzliche Ordner, die nicht zu WordPress gehören.

---

## Was ich damit mache

1. Theme-Zip auspacken, Inhalt in dieses Repo als ersten echten Commit
   (Repo-Root = Theme-Root, passend zum Deployment-Pfad aus
   `plesk-deploy.md`).
2. `bin/php-lint.sh` über den Bestand laufen lassen, offensichtliche Altlasten
   melden (fehlende Escapes, direkte DB-Zugriffe, hartcodierte URLs).
3. Entscheiden, ob wir am Theme selbst arbeiten oder ein Child-Theme brauchen
   (falls es ein fremdes Theme ist).
4. Deployment-Pfad in Plesk final festlegen und den Löschtest durchziehen.

## Nicht hierher gehören

* `wp-config.php` (enthält DB-Zugangsdaten und Salts)
* Datenbank-Dumps
* `wp-content/uploads/` (Medien; gehören nicht in Git)
* WordPress-Core-Dateien (macht das Repo gross und kollidiert mit Updates)

Alles davon ist in `.gitignore` bereits ausgeschlossen.
