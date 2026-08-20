# Ist-Zustand aktualisieren

Der erfasste Stand steht in `ist-zustand.md` (Stand 2026-08-20). Diese
Anleitung beschreibt, wie man ihn neu erhebt — nach Updates, nach einem
Plugin-Wechsel, oder wenn etwas nicht mehr zusammenpasst.

Aus einer Cloud-Session ist die Seite nicht abrufbar (siehe
`plesk-deploy.md`), der Stand kommt also aus dem Backend, nicht aus einem
HTTP-Abruf.

## 1. Site-Health-Bericht (liefert fast alles)

    https://realnorth.ch/wp-admin/site-health.php?tab=debug

Menüweg: **Werkzeuge -> Website-Zustand -> Reiter «Informationen»** ->
Button «Website-Informationen in die Zwischenablage kopieren».

Darin: WordPress-Version, aktives Theme inkl. Parent, alle Plugins mit
Versionen, PHP-/MariaDB-Version, Serverpfade, Konstanten, Limits.

Braucht die Rolle **Administrator** — als Redakteur ist der Menüpunkt
unsichtbar.

## 2. Ergänzend aus Plesk

* **WordPress Toolkit** (Plesk -> WordPress): Version, Theme, Plugins,
  Installationspfad auf einem Blick. Dort liegt auch die Klon-Funktion für
  eine Staging-Kopie.
* **PHP-Einstellungen** der Domain, falls die Version wechselt.

## 3. Theme oder Plugin als Datei

File Manager -> `httpdocs/realnorth/wordpress/wp-content/…` -> Ordner
markieren -> **Herunterladen** (Plesk zippt).

## Was nicht ins Repo gehört

* `wp-config.php` (DB-Zugangsdaten, Salts)
* Datenbank-Dumps
* `wp-content/uploads/` (206 MB Medien)
* WordPress-Core und Fremd-Plugins/-Themes

Alles davon ist in `.gitignore` ausgeschlossen. Zugangsdaten, Lizenzschlüssel
und Tokens gehören auch nicht in den Chat.
