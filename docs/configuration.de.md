# Felder konfigurieren

[🇬🇧 English version](configuration.en.md)

Die Felder des Tabs "Konfiguration" werden durch ein **Schema** in der Projekt-Konfiguration festgelegt. Die Werte
eines Artikels liegen als JSON in der Spalte `data` von `ar_article_configuration`. Ein Feld hinzuzufügen oder zu
entfernen erfordert deshalb nie eine Datenbank-Änderung.

## Ebenen

Das Schema eines Artikels wird aus vier Ebenen zusammengesetzt, eine spätere Ebene überschreibt die frühere:

| Ebene | Config-Key | Gilt für |
|-------|------------|----------|
| 1. Eingebaut | - | alle Artikel (die 11 mitgelieferten Felder) |
| 2. Default | `default` | alle Artikel |
| 3. Group | `groups.<group>` | alle Templates einer Artikel-Group (das `<group>` der Template-XML) |
| 4. Template | `templates.<templateKey>` | ein einzelnes Template |

Das Sulu-Admin zeigt pro Artikel genau einen Tab "Konfiguration", aufgebaut aus dem Schema des Templates, das der
Artikel verwendet. Wird das Template eines Artikels geändert und gespeichert, wechselt der Tab.

## Konfiguration

`config/packages/sulu_article_configuration.yaml`:

```yaml
sulu_article_configuration:
    default:
        fields:
            layoutStyle:
                default: narrow              # Standardwert eines eingebauten Felds ändern
            customCssClass: false            # Feld überall entfernen
    groups:
        blog:
            fields:
                heroVariant:                 # Feld für alle Templates der Group "blog" hinzufügen
                    type: single_select
                    values: [image, video]
                    default: image
                    section: hero
    templates:
        blog_post:
            fields:
                showToc: false               # Feld nur für dieses Template entfernen
                layoutStyle:
                    values: [narrow, wide]   # Auswahl eines Felds nur für dieses Template ändern
                    default: narrow
                readingSpeed:
                    type: number
                    default: 200
```

Beim Überschreiben eines Felds werden die angegebenen Optionen in die bestehende Definition gemischt, nur `values`
wird als Ganzes ersetzt. `false` entfernt das Feld. Ein Feld, das es noch nicht gibt, braucht mindestens einen `type`.

Nach Änderungen am Schema den Cache leeren: `bin/console cache:clear` (Sulu cached die Formular-Metadaten).

## Feld-Optionen

| Option | Beschreibung |
|--------|--------------|
| `type` | `toggle` (bool), `text` (String oder null), `single_select` (einer der `values`), `number` (int oder float) |
| `default` | Standardwert. `toggle` ist standardmäßig `false`, `single_select` der erste Wert, die anderen `null` |
| `values` | Liste erlaubter Werte, Pflicht bei `single_select` |
| `section` | Name der Sektion (Gruppenbox) im Formular. Felder ohne Sektion landen in "Weitere Optionen" |
| `colspan` | Breite im 12er-Raster, z. B. `6` |
| `visible_condition` | Sulu-Bedingung auf andere Felder des Formulars, z. B. `enableSidebar == true` |

Die vom Admin gesendeten Werte werden gegen das Schema geprüft: unbekannte Keys werden verworfen, falsche Typen
fallen auf den Standardwert zurück, ein `single_select` akzeptiert nur seine `values`.

## Übersetzungen

Labels sind Übersetzungs-Keys in der Domain `admin`, abgeleitet vom Feldnamen in snake_case. Im Projekt ergänzen,
z. B. `translations/admin.de.yaml`:

```yaml
sulu_article_configuration:
    hero: "Hero"                      # Label der Sektion "hero"
    hero_variant:                     # Feld "heroVariant"
        title: "Hero-Variante"
        info: "Bild oder Video im Artikel-Header."
        image: "Bild"                 # ein Key pro Wert einer Auswahl
        video: "Video"
```

Die mitgelieferten Felder sind bereits übersetzt (Deutsch und Englisch). Fehlende Keys werden als reiner Key
angezeigt.

## Frontend

Alle Felder, auch eigene, stehen in Twig zur Verfügung:

```twig
{% set articleConfig = article_config(uuid, template) %}

{% if articleConfig.heroVariant == 'video' %}
    ...
{% endif %}
```

`configSource` zeigt weiterhin die Herkunft der Werte (`article`, `template_default`, `hardcoded`). `hardcoded`
bedeutet die Standardwerte des Schemas. Zurückgegeben werden nur die Felder des Template-Schemas: Werte zu später
entfernten Feldern werden ignoriert, später hinzugekommene Felder mit ihrem Standardwert gefüllt.

## Umstieg von 2.x

3.0 verschiebt die Werte aus festen Spalten in die JSON-Spalte `data`. Die Daten-Migration **vor** dem
Schema-Update ausführen, sonst gehen die alten Werte verloren:

```bash
php bin/console sulu:article-configuration:migrate-to-json --dry-run
php bin/console sulu:article-configuration:migrate-to-json
php bin/console doctrine:schema:update --force
```

Der Befehl legt die Spalte `data` an, kopiert die alten Spalten hinein und kann beliebig oft ausgeführt werden. Das
anschließende Schema-Update entfernt die alten Spalten. Twig-API und Feldnamen bleiben gleich. Der frühere
Spalten-Default von `layoutStyle` (`default`) entfällt, neue Artikel verwenden den Schema-Default (`fullwidth`).
