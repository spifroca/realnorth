# realnorth

Plugin `realnorth-custom` — die projektspezifischen Code-Anpassungen der
WordPress-Seite **[realnorth.ch](https://realnorth.ch)**, gehostet auf Plesk
(`rlx1.loginserver.ch`).

Repo-Root entspricht auf dem Server:

    /httpdocs/realnorth/wordpress/wp-content/plugins/realnorth-custom/

Nicht im Repo: WordPress-Core, das PopularFX-Theme, Fremd-Plugins,
`wp-config.php`, Uploads, Datenbank.

## Einstieg

| Dokument | Inhalt |
|---|---|
| [`docs/ist-zustand.md`](docs/ist-zustand.md) | Stack, Pfade, Theme, Plugins, offene Risiken — und warum unser Code ein Plugin ist und kein Child-Theme. |
| [`docs/plesk-deploy.md`](docs/plesk-deploy.md) | Deployment GitHub -> Plesk: Feldwerte für den Plesk-Dialog, Webhook, Rollback. |
| [`docs/automatischer-deploy.md`](docs/automatischer-deploy.md) | Die automatische Kette Push -> Prüfung -> `main` -> live, die drei Schalter dafür und die Notausschalter. |
| [`docs/ist-zustand-erfassen.md`](docs/ist-zustand-erfassen.md) | Wie man den Ist-Zustand neu erhebt. |
| [`docs/cloud-environment.md`](docs/cloud-environment.md) | Cloud-Umgebung für Claude-Code-Sessions inkl. gemessener Netzwerk-Grenzen. |
| [`CLAUDE.md`](CLAUDE.md) | Arbeitsregeln und WordPress-Konventionen. |

## Aufbau

    realnorth-custom.php         Plugin-Header, lädt das Stylesheet (Priorität 20)
    includes/content-cleanup.php Textkorrekturen im Frontend (Notlösung, siehe CLAUDE.md)
    assets/css/site.css          projektspezifisches CSS
    bin/php-lint.sh              Syntax-Check, Pflicht vor dem Push
    bin/test.sh                  Tests, Pflicht vor dem Push
    tests/                       Regressionstests, laufen ohne WordPress
    docs/                        Doku (siehe oben)

## Deployment in einem Satz

Push auf `main` -> Plesk zieht den Stand in den Plugin-Ordner. Kein Build
auf dem Server (kein Shell-Zugriff), also muss alles Fertige eingecheckt
sein. Notausschalter: Plugin im wp-admin deaktivieren.

## Wichtig zu wissen

Die Seite ist mit dem Page-Builder **Pagelayer** gebaut. Layouts und
Inhalte liegen in der Datenbank, nicht in Dateien — sie werden im wp-admin
bearbeitet, nicht über dieses Repo.
