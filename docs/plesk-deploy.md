# Deployment: GitHub -> Plesk (realnorth.ch)

Wir arbeiten im Repo, pushen nach GitHub, **Plesk holt sich den Stand
selbst**. Diese Richtung ist nicht Geschmackssache, sondern die einzige, die
funktioniert (siehe «Warum Plesk zieht»).

## Fakten zur Umgebung

| | |
|---|---|
| Panel | https://rlx1.loginserver.ch:8443/ |
| Host / IP | `rlx1.loginserver.ch` -> `46.4.250.97` (identisch mit `realnorth.ch`) |
| Stack | WordPress 7.1, PHP 8.5.9, nginx 1.30.4, MariaDB 10.11 |
| WordPress-Root | `/httpdocs/realnorth/wordpress` (relativ zum Abo-Root) |
| Shell-Zugriff | **nein** |
| Repo | `spifroca/realnorth` (derzeit **öffentlich**, siehe Schritt 1) |
| Default-Branch | `main` |

Mehr zum Bestand: `ist-zustand.md`.

### Warum Plesk zieht und nicht wir pushen

Aus einer Claude-Cloud-Session ist der Server nicht erreichbar. Gemessen:

    curl https://realnorth.ch             -> 403 CONNECT tunnel failed
    curl https://rlx1.loginserver.ch      -> 403 CONNECT tunnel failed
    curl https://rlx1.loginserver.ch:8443 -> Connection reset by peer
    TCP 22                                -> dicht

Der Egress-Proxy erlaubt CONNECT praktisch nur auf Port 443. Das heisst:

* **rsync/SFTP/SSH-Deploy aus der Session: fällt weg.**
* **Panel-Automatisierung (Plesk-API auf 8443): fällt weg.** Auch mit
  Domain-Freischaltung in der Cloud-Umgebung bleibt der Port das Problem.
* Was immer geht: `git push` nach GitHub (läuft über den GitHub-Proxy der
  Session, unabhängig von der Netzwerk-Policy).

Deshalb: GitHub ist die Drehscheibe, Plesk der Konsument.

## Was deployt wird

Das Repo **ist** das Plugin `realnorth-custom`. Repo-Root entspricht
`.../wp-content/plugins/realnorth-custom/` auf dem Server.

Begründung für Plugin statt Theme: `ist-zustand.md`, Abschnitt «Warum
Plugin und nicht Child-Theme». Kurz: PopularFX ist ein Fremd-Theme, und ein
Theme-Wechsel würde die Customizer-Einstellungen zurücksetzen.

## Einmal-Einrichtung

### 0. Backup, bevor irgendwas eingerichtet wird

Plesk -> **Websites & Domains** -> realnorth.ch -> **Backup Manager** ->
Backup (Dateien + Datenbank). Wenn das Abo das nicht erlaubt: File Manager
-> `httpdocs/realnorth/wordpress/wp-content` -> `plugins` und `themes`
herunterladen.

Aufbewahren, bis der erste Deploy nachweislich sauber lief.

### 1. Sichtbarkeit des Repos entscheiden

**Stand heute: `spifroca/realnorth` ist öffentlich.** Das hat zwei Folgen:

* Bequem: Plesk kann ohne Schlüssel klonen —
  `https://github.com/spifroca/realnorth.git` genügt, Schritt 2 entfällt.
* Unbequem: der Code eines Kundenprojekts liegt offen, inklusive
  Commit-Historie. Für ein Kundenprojekt würde ich das Repo **auf privat
  stellen** (GitHub -> Settings -> General -> Danger Zone -> *Change
  visibility*) und Plesk per Deploy-Key anbinden (Schritt 2).

Bitte bewusst entscheiden, nicht aus Versehen öffentlich lassen.

### 2. Deploy-Key (nur bei privatem Repo)

1. Plesk -> realnorth.ch -> **Git** -> *Remote repository* -> URL
   `git@github.com:spifroca/realnorth.git` eintragen. Plesk zeigt danach
   einen **öffentlichen SSH-Schlüssel** an -> kopieren.
2. GitHub -> `spifroca/realnorth` -> **Settings** -> **Deploy keys** ->
   *Add deploy key*: Titel z. B. `plesk-rlx1`, Key einfügen,
   **«Allow write access» NICHT anhaken** (Plesk muss nur lesen).

Read-only ist wichtig: ein kompromittierter Server kann damit keinen Code
ins Repo schreiben.

### 3. Repository in Plesk anlegen

Plesk -> realnorth.ch -> **Git** -> *Create repository*:

| Feld | Wert |
|---|---|
| Code location | **Remote repository** |
| Repository URL | `https://github.com/spifroca/realnorth.git` (privat: `git@github.com:…`) |
| Repository name | `realnorth.git` (nur ein Plesk-interner Name) |
| Deployment mode | zuerst **Manual**, später *Automatic* |
| Server path | `/httpdocs/realnorth/wordpress/wp-content/plugins/realnorth-custom` |
| | *Achtung: relativ zum **Abo-Root**, nicht zu `httpdocs`. Und das Verzeichnis vorher anlegen — Plesk erstellt es nicht (siehe Stolpersteine).* |
| Enable additional deployment actions | leer lassen — braucht Shell-Zugriff, den das Abo nicht hat |

Zum Server-Pfad, weil hier der Schaden entsteht, wenn er falsch ist:

> Der Server-Pfad darf nur ein Verzeichnis sein, dessen kompletter Inhalt
> dem Repo gehört.

Der Plesk-Vorschlag `/httpdocs/realnorth/wordpress` ist der
**WordPress-Root** — dort niemals hin deployen. Ebenso tabu:
`.../wp-content` (enthält `uploads/` mit allen Medien und alle Plugins).
`.../plugins/realnorth-custom` existiert noch nicht und wird von Plesk
angelegt; dort kann nichts überschrieben werden.

Der Dialog hat kein Branch-Feld — Plesk nimmt den Default-Branch, und der
steht auf `main`.

### 4. Erster Deploy und Aktivierung

1. Plesk -> Git -> **Deploy** (manuell) auslösen.
2. File Manager -> `.../plugins/realnorth-custom/` -> es müssen
   `realnorth-custom.php`, `assets/`, `docs/`, `bin/` dort liegen.
3. wp-admin -> **Plugins** -> **realnorth Custom** -> *Aktivieren*.
   Ab jetzt greift unser CSS. Das ist ein Einmal-Schritt; spätere Deploys
   brauchen keine Aktivierung mehr.
4. Startseite und eine Unterseite prüfen.

Erst danach auf **Automatic** umstellen.

### 5. Auto-Deploy per Webhook

Plesk zeigt im Modus *Automatic* eine **Webhook-URL** (enthält ein Token,
zeigt auf Port 8443). Diese eintragen unter:

GitHub -> Repo -> **Settings** -> **Webhooks** -> *Add webhook*
* Payload URL: die URL aus Plesk
* Content type: `application/json`
* Secret: leer (das Token steckt in der URL)
* Events: **Just the push event**

Danach unter *Recent Deliveries* prüfen, ob der Ping mit 2xx zurückkommt.
Bei Timeout lässt die Server-Firewall GitHub nicht auf 8443 — dann bleibt
der Modus manuell («Deploy» nach jedem Push).

### 6. Löschverhalten testen (bevor wir uns darauf verlassen)

Unklar ist, ob Plesk Dateien, die wir im Repo **löschen**, auch auf dem
Server entfernt. Das ist versionsabhängig — also einmal messen statt raten:

1. Datei `_deploy-test.txt` committen, pushen, deployen -> im File Manager
   prüfen, dass sie da ist.
2. Datei im Repo löschen, pushen, deployen -> prüfen, ob sie **weg** ist.
3. Ergebnis hier notieren:

       Löschungen werden übernommen: [ ] ja   [ ] nein (manuell nachräumen)

Wenn nein: gelöschte oder umbenannte PHP-Dateien müssen im File Manager
entfernt werden, sonst laufen verwaiste Dateien auf dem Server mit.

### 7. Doku nicht ausliefern (optional)

`docs/` und `bin/` landen mit dem Deploy unter
`/wp-content/plugins/realnorth-custom/`. Inhaltlich ist das unkritisch
(keine Zugangsdaten, das Repo ist öffentlich), aber ausgeliefert werden
muss es nicht. `.htaccess` hilft hier nicht — der Server läuft nginx ohne
Apache. Stattdessen in Plesk unter **Apache & nginx Settings** ->
**Additional nginx directives**:

    location ~* /wp-content/plugins/realnorth-custom/(docs|bin|tests)/ { deny all; }

## Täglicher Ablauf

1. Änderung im Repo, `bin/php-lint.sh` läuft grün.
2. Commit + `git push`.
3. Auto-Deploy (oder Plesk -> Git -> *Deploy*).
4. Sichtprüfung auf realnorth.ch.

**Nie** Dateien im Plesk File Manager bearbeiten, die im Repo liegen — der
nächste Deploy überschreibt sie kommentarlos, und die Änderung ist nirgends
dokumentiert.

## Rollback

Der Deploy zieht immer den **Kopf des Branches**. Ein Rollback ist also ein
neuer Commit, kein Zurückspringen:

    git revert <commit-sha>
    git push

Seite kaputt und es muss schnell gehen: wp-admin -> Plugins -> **realnorth
Custom deaktivieren**. Das ist der Vorteil gegenüber einem Child-Theme oder
mu-plugin — der Notausschalter liegt im Backend, ohne Git und ohne File
Manager. Danach die Ursache im Repo suchen.

## Bekannte Stolpersteine

* **`fatal: Invalid path '…': No such file or directory`** beim Deploy —
  zwei Ursachen, beide im Feld *Server path*:

      fatal: Invalid path '/var/www/vhosts/realnorth.ch/realnorth':
      No such file or directory

  1. **Das Feld ist relativ zum Abo-Root**, nicht zu `httpdocs`. Steht dort
     `realnorth`, landet der Checkout in
     `/var/www/vhosts/realnorth.ch/realnorth` statt im Plugin-Ordner.
     Richtig ist der volle Pfad ab Abo-Root, siehe Schritt 3.
  2. **Plesk legt das Zielverzeichnis nicht an.** Es muss vor dem ersten
     Deploy im File Manager existieren:
     `httpdocs/realnorth/wordpress/wp-content/plugins/realnorth-custom`.
     Ein leerer Ordner dort ist harmlos — WordPress ignoriert ihn, solange
     keine Plugin-Datei drin liegt.

  Meldet Plesk dagegen «Validierung des Bereitstellungsschlüssels: Fertig»,
  ist die Verbindung zu GitHub in Ordnung; dann liegt es nur am Pfad.
* **Weisser Screen nach Deploy** — fast immer ein PHP-Syntaxfehler. Genau
  dagegen ist `bin/php-lint.sh` da; ohne Shell gibt es auf dem Server kein
  `php -l` als Rettung. Achtung: unser Lint läuft auf PHP 8.4, der Server
  auf 8.5.9.
* **Änderung nicht sichtbar** — CSS-Version hängt an `filemtime()`, das
  greift nach dem Deploy sofort. Bleibt es alt, ist ein Caching-Plugin oder
  der nginx-Cache in Plesk dran. Opcache ist auf diesem Server nicht aktiv,
  ist also nicht die Ursache.
* **Kein composer/npm auf dem Server** — ohne Shell laufen keine
  Deploy-Actions. Alles Gebaute muss eingecheckt sein.
* **Plugin-Ordner umbenennen** — dann muss der Server-Pfad in Plesk
  mitgeändert und das Plugin im wp-admin neu aktiviert werden. Also besser
  nicht.
