# realnorth

Code der WordPress-Website **[realnorth.ch](https://realnorth.ch)**, gehostet
auf Plesk (`rlx1.loginserver.ch`).

Das Repo enthält den selbst gepflegten Teil der Seite — Theme bzw.
Child-Theme. WordPress-Core, `wp-config.php`, Uploads und Datenbank sind
bewusst nicht drin.

## Einstieg

| Dokument | Inhalt |
|---|---|
| [`docs/ist-zustand-erfassen.md`](docs/ist-zustand-erfassen.md) | **Hier anfangen.** Was aus Plesk/WordPress einmal geliefert werden muss, damit der echte Stand der Seite ins Repo kommt. |
| [`docs/plesk-deploy.md`](docs/plesk-deploy.md) | Einrichtung des Deployments GitHub -> Plesk, Deployment-Pfad, Webhook, Rollback. |
| [`docs/cloud-environment.md`](docs/cloud-environment.md) | Cloud-Umgebung für Claude-Code-Sessions, inkl. gemessener Netzwerk-Einschränkungen. |
| [`CLAUDE.md`](CLAUDE.md) | Arbeitsregeln (Deploy-Richtung, WordPress-Konventionen, Lint-Pflicht). |

## Werkzeuge

    ./bin/php-lint.sh     # Syntax-Check aller PHP-Dateien, Pflicht vor dem Push

`.claude/hooks/session-start.sh` installiert in Cloud-Sessions automatisch
wp-cli.

## Deployment in einem Satz

Push auf `main` -> Plesk zieht den Stand per Git-Integration in
`httpdocs/wp-content/themes/<slug>/`. Kein Build auf dem Server (kein
Shell-Zugriff), also muss alles Fertige eingecheckt sein.
