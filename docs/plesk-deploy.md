# Deployment: GitHub -> Plesk (realnorth.ch)

Ziel: Wir arbeiten im Repo, pushen nach GitHub, **Plesk holt sich den Stand
selbst**. Diese Richtung ist nicht Geschmackssache, sondern die einzige, die
funktioniert (siehe «Warum Plesk zieht»).

## Fakten zur Umgebung

| | |
|---|---|
| Panel | https://rlx1.loginserver.ch:8443/ |
| Host / IP | `rlx1.loginserver.ch` -> `46.4.250.97` (identisch mit `realnorth.ch`) |
| Stack | WordPress auf Plesk (Apache + nginx davor) |
| Shell-Zugriff | **nein** (Stand: bestätigt) |
| Repo | `spifroca/realnorth` (derzeit **öffentlich**, siehe Schritt 1) |
| Default-Branch | `main` — muss noch angelegt werden, das Repo hat aktuell keinen Branch |

### Warum Plesk zieht und nicht wir pushen

Aus einer Claude-Cloud-Session ist der Server nicht erreichbar. Gemessen:

    curl https://realnorth.ch            -> 403 CONNECT tunnel failed
    curl https://rlx1.loginserver.ch     -> 403 CONNECT tunnel failed
    curl https://rlx1.loginserver.ch:8443 -> Connection reset by peer
    TCP 22                                -> dicht

Der Egress-Proxy erlaubt CONNECT praktisch nur auf Port 443. Das heisst:

* **rsync/SFTP/SSH-Deploy aus der Session: fällt weg.**
* **Panel-Automatisierung (Plesk-API auf 8443): fällt weg.** Auch mit
  Domain-Freischaltung in der Cloud-Umgebung bleibt der Port das Problem.
* Was immer geht: `git push` nach GitHub (läuft über den GitHub-Proxy der
  Session, unabhängig von der Netzwerk-Policy).

Deshalb: GitHub ist die Drehscheibe, Plesk der Konsument.

## Einmal-Einrichtung

Reihenfolge einhalten — Schritt 0 ist der Rückweg, falls Schritt 4 schiefgeht.

### 0. Backup, bevor irgendwas eingerichtet wird

Plesk -> **Websites & Domains** -> realnorth.ch -> **File Manager** ->
`httpdocs/wp-content/themes` -> aktives Theme markieren -> **Herunterladen**
(Plesk zippt den Ordner). Zusätzlich, wenn im Abo erlaubt: **Backup Manager**
-> *Backup* (Dateien + Datenbank).

Das Zip bitte aufbewahren, bis der erste Deploy nachweislich sauber lief.

### 1. Sichtbarkeit des Repos entscheiden

**Stand heute: `spifroca/realnorth` ist öffentlich.** Das hat zwei Folgen:

* Bequem: Plesk kann ohne Schlüssel klonen —
  `https://github.com/spifroca/realnorth.git` genügt, Schritt 2 entfällt.
* Unbequem: der komplette Theme-Code eines Kundenprojekts liegt offen,
  inklusive Commit-Historie. Für ein Kundenprojekt würde ich das Repo
  **auf privat stellen** (GitHub -> Settings -> General -> Danger Zone ->
  *Change visibility*) und Plesk per Deploy-Key anbinden (Schritt 2).

Beides ist vertretbar — nur bitte bewusst entscheiden, nicht aus Versehen
öffentlich lassen. Wenn privat: Schritt 2 machen und in Schritt 4 die
SSH-URL eintragen.

### 2. Deploy-Key (nur bei privatem Repo)

1. Plesk -> realnorth.ch -> **Git** -> Repository hinzufügen ->
   **Remote Git repository** -> URL eintragen.
   Plesk zeigt danach einen **öffentlichen SSH-Schlüssel** an -> kopieren.
2. GitHub -> `spifroca/realnorth` -> **Settings** -> **Deploy keys** ->
   *Add deploy key*: Titel z. B. `plesk-rlx1`, Key einfügen,
   **«Allow write access» NICHT anhaken** (Plesk muss nur lesen).

Read-only ist wichtig: ein kompromittierter Server kann damit keinen Code
ins Repo schreiben.

### 3. Deployment-Pfad — der kritische Schritt

Plesk checkt den **Repo-Inhalt in den Deployment-Pfad** aus. Regel:

> Der Deployment-Pfad darf nur ein Verzeichnis sein, dessen kompletter Inhalt
> dem Repo gehört.

Richtig:

    httpdocs/wp-content/themes/<theme-slug>

Falsch, und zwar gefährlich:

* `httpdocs` — dort liegen WordPress-Core und `wp-config.php`.
* `httpdocs/wp-content` — dort liegen `uploads/` (alle Medien!) und alle
  Plugins, die niemand im Repo hat.

Wenn die Seite ein **fremdes Theme** (gekauft/aus dem WP-Verzeichnis)
benutzt: dieses Theme **nicht** ins Repo nehmen, sondern ein **Child-Theme**
anlegen und nur dieses deployen. Sonst löscht das nächste Theme-Update im
wp-admin unsere Änderungen bzw. unser Deploy überschreibt das Update.

### 4. Git-Repository in Plesk anlegen

Plesk -> realnorth.ch -> **Git** -> *Remote Git repository*:

| Feld | Wert |
|---|---|
| Repository-URL | öffentlich: `https://github.com/spifroca/realnorth.git` — privat: `git@github.com:spifroca/realnorth.git` |
| Branch | `main` |
| Deployment-Pfad | `httpdocs/wp-content/themes/<theme-slug>` |
| Deployment-Modus | zuerst **manuell**, später *automatisch* |
| Additional deploy actions | steht ohne Shell-Zugriff nicht zur Verfügung — Feld bitte gegenprüfen und mir melden |

Erst **manuell** deployen. Dann einmal die Seite prüfen (Startseite, eine
Unterseite, wp-admin). Erst danach auf automatisch umstellen.

### 5. Auto-Deploy per Webhook

Plesk zeigt beim Modus *automatisch* eine **Webhook-URL** (enthält ein Token,
zeigt auf Port 8443). Diese eintragen unter:

GitHub -> Repo -> **Settings** -> **Webhooks** -> *Add webhook*
* Payload URL: die URL aus Plesk
* Content type: `application/json`
* Secret: leer (das Token steckt in der URL)
* Events: **Just the push event**

Danach in GitHub unter *Recent Deliveries* prüfen, ob der Ping mit 2xx
zurückkommt. Kommt ein Timeout: Firewall des Servers lässt GitHub nicht auf
8443 — dann bleibt der Modus manuell («Pull now» nach jedem Push).

### 6. Löschverhalten testen (bevor wir uns darauf verlassen)

Unklar ist, ob Plesk Dateien, die wir im Repo **löschen**, auch auf dem Server
entfernt. Das ist versionsabhängig — also einmal messen statt raten:

1. Datei `_deploy-test.txt` committen und pushen -> deployen -> im File
   Manager prüfen, dass sie da ist.
2. Datei im Repo löschen, pushen -> deployen -> prüfen, ob sie **weg** ist.
3. Ergebnis hier notieren:

       Löschungen werden übernommen: [ ] ja   [ ] nein (manuell nachräumen)

Wenn nein: umbenannte/gelöschte Templates müssen im File Manager gelöscht
werden, sonst laufen verwaiste PHP-Dateien auf dem Server mit.

## Täglicher Ablauf

1. Änderung im Repo, `bin/php-lint.sh` läuft grün.
2. Commit + `git push`.
3. Auto-Deploy (oder Plesk -> Git -> *Pull now*).
4. Sichtprüfung auf realnorth.ch.

**Nie** Dateien im Plesk File Manager direkt bearbeiten, die im Repo liegen —
der nächste Deploy überschreibt sie kommentarlos, und die Änderung ist
nirgends dokumentiert.

## Rollback

Der Deploy zieht immer den **Kopf des Branches**. Ein Rollback ist also ein
neuer Commit, kein Zurückspringen:

    git revert <commit-sha>
    git push

Seite komplett kaputt (weisser Screen)? Sofortmaßnahme ohne Git:
File Manager -> Theme-Ordner löschen -> Zip aus Schritt 0 hochladen und
entpacken. Danach Ursache im Repo suchen.

## Bekannte Stolpersteine

* **Weisser Screen nach Deploy** — fast immer ein PHP-Syntaxfehler. Genau
  dagegen ist `bin/php-lint.sh` da; ohne Shell gibt es auf dem Server kein
  `php -l` als Rettung.
* **Änderung nicht sichtbar** — Caching-Plugin oder nginx-Cache in Plesk
  leeren, dann Browser hart neu laden.
* **Kein composer/npm auf dem Server** — ohne Shell laufen keine Deploy-Actions.
  Alles Gebaute (kompiliertes CSS/JS, `vendor/`, wenn wirklich nötig) muss
  eingecheckt sein, sonst fehlt es live.
* **Datei-Eigentümer** — Plesk deployt als Systembenutzer des Abos. Wenn
  WordPress später Dateien im Theme schreiben will (Editor im wp-admin), kann
  das kollidieren. Der Theme-Editor im wp-admin sollte ohnehin ungenutzt
  bleiben, solange das Theme aus Git kommt.
