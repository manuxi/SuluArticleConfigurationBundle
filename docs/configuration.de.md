# Felder konfigurieren

[🇬🇧 English version](configuration.en.md)

Die Felder des Tabs "Konfiguration" werden mit normalem **Sulu-Formular-XML** definiert, im selben Format wie die
Dateien in `config/forms/`. Die Werte eines Artikels liegen als JSON in der Spalte `data` von
`ar_article_configuration`. Ein Feld hinzuzufügen oder zu entfernen erfordert deshalb nie eine Datenbank-Änderung.

## Ebenen

Das Formular eines Artikel-Templates wird aus drei XML-Formularen zusammengesetzt. Eine spätere Ebene verändert die
frühere, jede Ebene ist optional:

| Ebene | `<key>` des Formulars | Datei in deinem Projekt |
|-------|-----------------------|-------------------------|
| 1. Basis | `article_configuration` | kommt mit dem Bundle (die 11 Standardfelder), kann erweitert werden |
| 2. Group | `article_configuration_group_<group>` | `config/article_configuration/group_<group>.xml` |
| 3. Template | `article_configuration_template_<templateKey>` | `config/article_configuration/template_<templateKey>.xml` |

- `<group>` ist das `<group>` der Artikel-Template-XML, `<templateKey>` der `<key>` des Artikel-Templates.
- Der Ordner `config/article_configuration/` wird automatisch registriert, sobald er existiert, eine weitere
  Konfiguration ist nicht nötig. Pro Key genau **eine Datei** hineinlegen.
- Das Sulu-Admin zeigt pro Artikel einen Tab "Konfiguration", aufgebaut aus dem Formular des Templates, das der
  Artikel verwendet. Wird das Template eines Artikels geändert und gespeichert, wechselt der Tab.

## Was eine Ebene kann

Group- und Template-Dateien enthalten nur die Unterschiede:

| Du willst | Du schreibst |
|-----------|--------------|
| ein Feld hinzufügen | eine `<property>` in einer `<section>` (eine unbekannte Sektion wird neu angelegt) |
| ein Feld ändern | eine `<property>` mit demselben `name`. Sie ersetzt die ganze Definition und behält die Position |
| ein Feld entfernen | die Property mit `<tag name="article_configuration.remove"/>` |
| eine Sektion umbenennen | eine `<section>` mit demselben `name` und einem `<meta><title>`; ihre Felder werden mit den vorhandenen zusammengeführt |

Feldnamen sind die JSON-Keys und müssen im ganzen Formular eindeutig sein. Ein auf einer Ebene entferntes Feld kann
auf einer späteren Ebene wieder hinzugefügt werden. Sektionen, die am Ende leer sind, verschwinden.

Beispiel `config/article_configuration/template_blog_post.xml`:

```xml
<?xml version="1.0" ?>
<form xmlns="http://schemas.sulu.io/template/template"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://schemas.sulu.io/template/template http://schemas.sulu.io/template/form-1.0.xsd"
>
    <key>article_configuration_template_blog_post</key>
    <properties>
        <section name="display_options">
            <properties>
                <property name="showToc" type="checkbox">
                    <tag name="article_configuration.remove"/>
                </property>
                <property name="layoutStyle" type="single_select" colspan="6">
                    <meta>
                        <title>sulu_article_configuration.layout_style.title</title>
                    </meta>
                    <params>
                        <param name="default_value" value="narrow"/>
                        <param name="values" type="collection">
                            <param name="narrow">
                                <meta><title>sulu_article_configuration.layout_style.narrow</title></meta>
                            </param>
                            <param name="wide">
                                <meta><title>sulu_article_configuration.layout_style.wide</title></meta>
                            </param>
                        </param>
                    </params>
                </property>
            </properties>
        </section>
        <section name="more">
            <meta>
                <title>app.more</title>
            </meta>
            <properties>
                <property name="readingSpeed" type="number">
                    <meta>
                        <title lang="en">Words per minute</title>
                        <title lang="de">Wörter pro Minute</title>
                    </meta>
                    <params>
                        <param name="default_value" value="200"/>
                    </params>
                </property>
            </properties>
        </section>
    </properties>
</form>
```

Nach dem Ändern oder Hinzufügen von XML-Dateien den Cache leeren (`bin/adminconsole cache:clear`). Das ist auch einmal
nötig, nachdem der Ordner `config/article_configuration/` zum ersten Mal angelegt wurde. Im Debug-Modus baut Sulu die
Formulare bei Bedarf neu auf.

## Werte-Typen

Der Typ eines Werts folgt dem Feldtyp im XML, der Standardwert kommt aus dem Param `default_value`. Die vom Admin
gesendeten Werte werden gegen das Formular geprüft: unbekannte Keys werden verworfen, falsche Werte fallen auf den
Standardwert zurück.

| Feldtyp | Gespeichert als | Standardwert |
|---------|-----------------|--------------|
| `checkbox` | bool | `default_value`, sonst `false` |
| `single_select` | einer der `values` | `default_value`, falls erlaubt, sonst der erste Wert |
| `select` | Liste erlaubter `values` | leere Liste |
| `number` | int oder float | `default_value`, falls numerisch, sonst `null` |
| `text_line`, `text_area`, `email`, `url`, `phone`, `color`, `date`, `time`, `datetime` | String oder `null` | `default_value` oder `null` |
| alles andere, z. B. `media_selection` | unverändert (JSON-kompatibel) | `null` |

Das Feld `default` ("Als Standard verwenden") ist reserviert: Es liegt in einer eigenen Spalte und ist nicht Teil
von `data`.

## Übersetzungen

Titel und Info-Texte sind normale Sulu-`<meta>`-Elemente: entweder ein Übersetzungs-Key der Domain `admin` oder
Text pro Sprache (`<title lang="de">`). Die Standardfelder sind vom Bundle auf Deutsch und Englisch übersetzt. Für
eigene Felder die Keys im Projekt ergänzen, z. B. `translations/admin.de.yaml`.

## Frontend

Alle Felder, auch eigene, stehen in Twig zur Verfügung:

```twig
{% set articleConfig = article_config(uuid, template) %}

{% if articleConfig.readingSpeed > 0 %}
    ...
{% endif %}
```

`configSource` zeigt weiterhin die Herkunft der Werte (`article`, `template_default`, `hardcoded`). `hardcoded`
bedeutet die Standardwerte des Formulars. Zurückgegeben werden nur die Felder des Formulars des Templates: Werte zu
später entfernten Feldern werden ignoriert, später hinzugekommene Felder mit ihrem Standardwert gefüllt.

## Umstieg von 2.x

3.0 verschiebt die Werte aus festen Spalten in die JSON-Spalte `data`. Die Daten-Migration **vor** dem
Schema-Update ausführen, sonst gehen die alten Werte verloren:

```bash
php bin/adminconsole sulu:article-configuration:migrate-to-json --dry-run
php bin/adminconsole sulu:article-configuration:migrate-to-json
php bin/adminconsole doctrine:schema:update --force
```

Der Befehl legt die Spalte `data` an, kopiert die alten Spalten hinein und kann beliebig oft ausgeführt werden. Das
anschließende Schema-Update entfernt die alten Spalten. Twig-API und Namen der Standardfelder bleiben gleich. Der
frühere Spalten-Default von `layoutStyle` (`default`) entfällt, neue Artikel verwenden den Standard des Formulars
(`fullwidth`).
