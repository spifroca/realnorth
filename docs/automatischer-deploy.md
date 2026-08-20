# Automatischer Deploy: von Claude bis live

Ziel: Claude ändert etwas, und die Änderung geht ohne Handgriff live —
aber nur, wenn sie geprüft ist.

## Die Kette

    Claude committet
        └─> Push auf claude/**                 (macht Claude)
              └─> GitHub Actions: Lint + Tests (automatisch)
                    └─> grün? Push nach main   (automatisch, Schalter AUTO_PROMOTE)
                          └─> Webhook an Plesk (automatisch)
                                └─> Plesk deployt in den Plugin-Ordner
                                      └─> live

Rot bei Lint oder Tests heisst: **main bleibt stehen, nichts geht live.**
Das ist der Grund für den Umweg über den Arbeitsbranch. Würde Claude direkt
auf main pushen, wäre ein PHP-Syntaxfehler unmittelbar ein weisser Screen —
und auf dem Server gibt es ohne Shell-Zugriff kein `php -l`, um ihn zu
finden.

## Was du einmal einrichten musst

Drei Schalter, danach läuft es.

### 1. GitHub: Actions darf nach main pushen

Repo -> **Settings** -> **Actions** -> **General** -> Abschnitt
*Workflow permissions* -> **Read and write permissions** -> *Save*.

Ohne das scheitert der Push-Schritt mit `403`.

### 2. GitHub: Automatik einschalten

Repo -> **Settings** -> **Secrets and variables** -> **Actions** -> Reiter
**Variables** -> *New repository variable*:

| Name | Wert |
|---|---|
| `AUTO_PROMOTE` | `true` |

Das ist der Hauptschalter. Variable auf `false` setzen oder löschen =
Claude pusht weiter, es wird weiter geprüft, aber **nichts geht mehr
automatisch live**. Kein Codeeingriff nötig.

### 3. Plesk: Auto-Deploy

Plesk -> realnorth.ch -> **Git** -> Repository-Einstellungen:

* Deployment mode auf **Automatic**
* die angezeigte **Webhook-URL** kopieren
* GitHub -> Repo -> **Settings** -> **Webhooks** -> *Add webhook*:
  Payload URL = die URL aus Plesk, Content type `application/json`,
  Events **Just the push event**

Prüfen unter *Recent Deliveries*, ob der Ping mit 2xx zurückkommt. Timeout
heisst: die Server-Firewall lässt GitHub nicht auf Port 8443 — dann bleibt
es bei «Deploy» per Knopf, der Rest der Kette funktioniert trotzdem.

Details zur Einrichtung des Repositories: `plesk-deploy.md`.

## Was geprüft wird

* `bin/php-lint.sh` — `php -l` über alle PHP-Dateien
* `bin/test.sh` — alles unter `tests/`

Der Runner benutzt die PHP-Version des GitHub-Images (derzeit 8.3), lokal
läuft 8.4, der Server 8.5.9. Für Syntaxfehler reicht das; bei
versionsspezifischer Syntax ist Vorsicht angebracht.

**Diese Prüfung ist nur so gut wie die Tests.** Sie fängt Syntaxfehler und
gebrochene Logik in getesteten Funktionen. Sie fängt nicht: falsches CSS,
kaputtes Layout, eine Regel die zu viel entfernt, oder Pagelayer-Eigenheiten.
Wer eine Änderung an der Ausgabe macht, schreibt einen Testfall dazu.

## Wenn etwas live schiefgeht

Drei Notausschalter, vom schnellsten zum grössten:

1. **Plugin deaktivieren** — wp-admin -> Plugins -> *realnorth Custom*.
   Wirkt sofort, ohne Git, ohne Plesk. Deshalb ist unser Code ein Plugin.
2. **Automatik aus** — `AUTO_PROMOTE` auf `false`. Danach bleibt main stehen,
   bis das Problem behoben ist.
3. **Zurückrollen** — `git revert <sha>` auf dem Arbeitsbranch, pushen; die
   Kette schiebt den Rückbau genauso automatisch live.

## Was das für dich bedeutet

Du gibst damit die manuelle Freigabe pro Änderung auf. Der Gegenwert ist
Geschwindigkeit; der Preis ist, dass geprüfter, aber inhaltlich falscher
Code live gehen kann. Wenn dir das zu weit geht, gibt es die Zwischenstufe:
`AUTO_PROMOTE` weglassen und main per Pull-Request-Merge selbst
weiterschieben. Die Prüfung läuft dann trotzdem und du siehst am PR, ob es
grün ist.
