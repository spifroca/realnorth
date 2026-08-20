# Konzept: neue Website realnorth.ch

Stand 2026-08-20. Ausgangslage: `ist-zustand.md`. Gestaltung: siehe
Design-Canvas (drei Richtungen, Entscheid offen).

Ziel laut Auftrag: aktuelle Bauprojekte, Immobilienbewirtschaftung, freie
Wohnungen — moderner und dynamischer als propertyone.ch oder
mozzattischlumpf.ch.

## Der entscheidende Punkt zuerst: woher kommen die Wohnungen

Der Auftrag sagt «freie Wohnungen direkt von Comparis». Das geht so nicht,
und zwar nicht wegen uns:

**Comparis ist Empfänger, nicht Sender.** Bewirtschaftungssoftware (z. B.
Immomig, CASASOFT und andere) schickt Inserate per Schnittstelle *an*
Comparis — kostenlos, ohne Mengenbegrenzung, Freischaltung über
`immobilien@comparis.ch`. Eine öffentliche Comparis-API, um die eigenen
Inserate wieder *herauszuholen*, gibt es nicht. Scraping wäre die einzige
technische Alternative und ist es nicht wert: es bricht bei jedem
Redesign, verstösst absehbar gegen die Nutzungsbedingungen und liefert
schlechtere Daten als die Quelle.

**Richtige Architektur:** die Website zieht aus derselben Quelle, die
Comparis füttert.

    Bewirtschaftungssoftware  ──┬──►  Comparis, Homegate, ImmoScout
       (Datenhoheit)            └──►  realnorth.ch  (unser Importer)

Damit ist die Website gleichwertig zu den Portalen statt von ihnen
abhängig, und die Daten sind eher aktueller als dort.

**Was ich dafür brauche:** welche Bewirtschaftungssoftware im Einsatz ist
und welchen Export sie kann. Erfahrungsgemäss gibt es eines davon:
IDX/immoXML, ein JSON- oder CSV-Feed, oder eine REST-API mit Token.

Der Importer wird deshalb **quellen-agnostisch** gebaut: ein Adapter pro
Format, dahinter ein einheitliches Datenmodell. Fällt die Entscheidung
später anders aus, wird ein Adapter getauscht, nicht die Website.
Zwischenlösung, falls es keinen Export gibt: Wohnungen direkt in
WordPress pflegen — das gleiche Datenmodell, nur von Hand gefüllt.

## Technische Grundsatzentscheidung: Block-Theme statt Page-Builder

Heute: PopularFX + Pagelayer. Das bleibt nicht, aus drei Gründen.

1. **Layouts liegen in der Datenbank.** Nichts davon ist versionierbar,
   reviewbar oder rollbackfähig. Genau das haben wir gerade eingerichtet.
2. **Der Rückstand ist strukturell.** Pagelayer 1.8.8 gegen 2.1.8, Pro
   ohne Lizenz — ein Update-Sprung, der Layouts bricht, steht ohnehin an.
   Wenn schon Bruch, dann in Richtung Zukunft.
3. **Dynamik braucht Code, nicht Klickstrecken.** Live-Filter,
   Feed-Import, Baufortschritt, Suchabos — das schreibt man, statt es zu
   konfigurieren.

Also: **eigenes Block-Theme** (Full Site Editing), im Repo, plus eigene
Blocks für die dynamischen Teile. Redaktionell bleibt der Block-Editor
für Inhalte zuständig — Struktur und Verhalten kommen aus Git.

## Plugin-Entscheid

### Bleibt

| Plugin | Warum |
|---|---|
| **WPForms** | 78 Bestandseinträge und 2 Formulare. Ein Wechsel kostet Migration ohne Gegenwert. Aber: auf aktuelle Version bringen (Lizenzfrage klären). |

### Kommt

| Plugin | Zweck | Warum dieses |
|---|---|---|
| **realnorth-custom** (eigenes, dieses Repo) | Datenmodell, Feed-Importer, Suche-Endpoint, Blocks | Kernlogik gehört uns, nicht einem Anbieter |
| **realnorth Theme** (eigenes Block-Theme) | Gestaltung, Templates | Volle Kontrolle, alles in Git |
| **Rank Math SEO** | Meta, Sitemaps, Schema.org | Schlanker als Yoast in der Gratis-Version, und liefert `RealEstateListing`-Schema — wichtig, damit Wohnungen in der Google-Suche als Objekte erscheinen |
| **Safe SVG** | Logo/Icons als SVG | WordPress erlaubt SVG-Upload sonst nicht, und ungefiltert wäre es ein XSS-Loch |

Bewusst **nicht** eingesetzt:

* **Kein Filter-Plugin** (FacetWP o. ä.). Die Wohnungssuche ist ein
  eigener REST-Endpoint plus rund 100 Zeilen JavaScript. Das ist schneller
  als ein generisches Plugin, kostet keine Lizenz und lässt sich genau so
  gestalten, wie es der Canvas zeigt.
* **Kein Immobilien-Plugin** (WP Residence, Estatik o. ä.). Die bringen
  ein eigenes Datenmodell, eigene Templates und eigene Update-Zyklen mit —
  wir brauchen nur einen Bruchteil davon und würden gegen das Plugin
  arbeiten statt mit ihm.
* **Kein Cache-Plugin vorerst.** Plesk kann nginx-Caching, und ohne
  Opcache und mit einem schlanken Theme ist das Budget vorhanden. Erst
  messen, dann optimieren.
* **Kein Page-Builder.**

### Geht

PopularFX (Theme), PageLayer, Pagelayer Pro, PopularFX Website Templates,
UltraEmbed, Hello Dolly, WPForms Lite. Akismet nur behalten, wenn
Kommentare offen bleiben — sonst weg.

**Reihenfolge beachten:** Pagelayer erst deaktivieren, wenn alle Inhalte
migriert sind. Pagelayer-Inhalte sind in einem eigenen Format in der
Datenbank; nach der Deaktivierung sind sie im Editor nicht mehr lesbar.
Die Migration ist Handarbeit, Seite für Seite — das ist der grösste
Einzelposten im Projekt und kein Skript.

## Datenmodell

    bauprojekt (CPT)
      Titel, Beschrieb, Galerie
      Felder: Ort, Status (Baueingabe|Bau|Ausbau|fertig),
              Baufortschritt %, Anzahl Wohnungen, Bezugstermin,
              Bauherr, Architektur, Verkaufs-/Vermietungsstart
      Taxonomie: Nutzung (Wohnen|Gewerbe|gemischt), Gemeinde

    wohnung (CPT, überwiegend importiert)
      Felder: Objekt-ID (Quelle), Zimmer, Fläche, Etage, Miete brutto/netto,
              Nebenkosten, verfügbar ab, Adresse, Geo, Bilder, Dokumente,
              Status (frei|reserviert|vermietet)
      Relation: gehört zu liegenschaft / bauprojekt

    liegenschaft (CPT, intern)
      Adresse, Eigentümer-Referenz, Einheiten — Grundlage für Reporting
      und für die Zuordnung der Wohnungen

Importierte Felder sind schreibgeschützt im Backend (sonst überschreibt
der nächste Feed-Lauf die Handarbeit). Redaktionell ergänzbar bleiben
Texte und Bilder pro Objekt.

## Importer

* Adapter pro Quellformat, gemeinsames internes Schema.
* Lauf per WP-Cron, stündlich, plus Button «jetzt importieren».
* Idempotent über die Objekt-ID der Quelle: bestehende Objekte werden
  aktualisiert, verschwundene auf `vermietet` gesetzt statt gelöscht
  (Permalinks und Statistik bleiben erhalten).
* Jeder Lauf schreibt ein Protokoll: gelesen, neu, geändert, Fehler.
* Bilder werden einmal in die Mediathek übernommen, nicht bei jedem Lauf.

**Zu prüfen:** WP-Cron läuft nur, wenn die Seite Besucher hat. Ob das
Plesk-Abo ohne Shell «Geplante Aufgaben» erlaubt, ist offen — wenn ja,
echter Cron-Aufruf auf `wp-cron.php`; wenn nein, reicht WP-Cron bei
stündlichem Ziel in der Praxis aus.

## Deploy-Struktur

Ein Plesk-Git-Repository deployt in genau ein Verzeichnis. Wir brauchen
zwei:

| Repo | Server-Pfad |
|---|---|
| `spifroca/realnorth` (dieses) | `…/wp-content/plugins/realnorth-custom` |
| `spifroca/realnorth-theme` (neu) | `…/wp-content/themes/realnorth` |

Beide als *Remote repository* mit demselben Muster (`plesk-deploy.md`).
Die Trennung ist nicht Bürokratie: das Datenmodell muss ein Redesign
überleben, das Theme darf ersetzbar bleiben.

## Reihenfolge

1. Gestaltungsrichtung entscheiden (Canvas), Marke und Inhalte klären.
2. Datenquelle für Wohnungen klären — das bestimmt den Importer.
3. Block-Theme aufsetzen, Startseite in der gewählten Richtung.
4. Datenmodell und Importer, zuerst gegen Beispieldaten.
5. Wohnungssuche und Objekt-Dossier.
6. Bauprojekte und Bewirtschaftung.
7. Inhalte aus Pagelayer migrieren, Seite für Seite.
8. Umschalten: Theme aktivieren, alte Plugins abschalten, Redirects
   prüfen, Sitemap neu einreichen.
9. Erst danach die alten Plugins löschen.

Auf einem Klon aus dem Plesk WordPress Toolkit entwickeln, nicht live.

## Offene Fragen

1. **Bewirtschaftungssoftware** und ihr Exportformat.
2. **Marke**: Logo, Farben, Schrift — gibt es etwas, oder wird es neu
   entwickelt? Der Canvas zeigt drei Vorschläge ins Blaue.
3. **Inhalte**: Projektnamen, Zahlen, Referenzen, Team, Adresse. Alles in
   `[Klammern]` im Canvas ist eine offene Stelle.
4. **Sprachen**: nur Deutsch, oder auch FR/EN? Beeinflusst das Theme von
   Anfang an.
5. **Eigentümer-Login** (Reporting, Dokumente) — Teil des Projekts oder
   später?
6. **Comparis**: soll die neue Seite auch *an* Comparis liefern, oder
   bleibt das Aufgabe der Bewirtschaftungssoftware?
