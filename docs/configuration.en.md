# Configuring the fields

[🇩🇪 Deutsche Version](configuration.de.md)

The fields of the "Configuration" tab are defined with plain **Sulu form XML**, the same format as the files in
`config/forms/`. The values of an article are stored as JSON in the column `data` of `ar_article_configuration`, so
adding or removing a field never needs a database change.

## Levels

The form of an article template is composed from three XML forms. A later level changes the earlier one, every
level is optional:

| Level | `<key>` of the form | File in your project |
|-------|---------------------|----------------------|
| 1. Base | `article_configuration` | shipped with the bundle (the 11 standard fields), can be extended |
| 2. Group | `article_configuration_group_<group>` | `config/article_configuration/group_<group>.xml` |
| 3. Template | `article_configuration_template_<templateKey>` | `config/article_configuration/template_<templateKey>.xml` |

- `<group>` is the `<group>` of the article template XML, `<templateKey>` the `<key>` of the article template.
- The directory `config/article_configuration/` is registered automatically as soon as it exists, no further
  configuration is needed. Put exactly **one file per key** into it.
- The Sulu admin shows one "Configuration" tab per article, built from the form of the template the article uses.
  Changing the template of an article and saving it switches the tab.

## What a level can do

Group and template files only contain the differences:

| You want to | You write |
|-------------|-----------|
| add a field | a `<property>` in a `<section>` (a new section is created if the name is unknown) |
| change a field | a `<property>` with the same `name`. It replaces the whole definition of the field and keeps its position |
| remove a field | the property with `<tag name="article_configuration.remove"/>` |
| rename or relabel a section | a `<section>` with the same `name` and a `<meta><title>`; its fields are merged with the existing ones |

Field names are the JSON keys, so they must be unique across the whole form. A field removed on one level can be
added again on a later level. Sections that end up empty disappear.

Example `config/article_configuration/template_blog_post.xml`:

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

After changing or adding XML files, clear the cache (`bin/adminconsole cache:clear`). This is also needed once after
creating the directory `config/article_configuration/` for the first time. In debug mode Sulu rebuilds the forms on
demand.

## Value types

The type of a value follows the field type in the XML, the default comes from the param `default_value`.
Values sent by the admin are validated against the form: unknown keys are dropped and wrong values fall back to the
default.

| Field type | Stored as | Default |
|------------|-----------|---------|
| `checkbox` | bool | `default_value`, otherwise `false` |
| `single_select` | one of the `values` | `default_value` if allowed, otherwise the first value |
| `select` | list of allowed `values` | empty list |
| `number` | int or float | `default_value` if numeric, otherwise `null` |
| `text_line`, `text_area`, `email`, `url`, `phone`, `color`, `date`, `time`, `datetime` | string or `null` | `default_value` or `null` |
| everything else, e.g. `media_selection` | stored as delivered (JSON compatible) | `null` |

The field `default` ("Use as default") is reserved: it is kept in its own column and is not part of `data`.

## Translations

Titles and info texts are normal Sulu `<meta>` elements: either a translation key of the `admin` domain or text per
language (`<title lang="de">`). The standard fields are translated in German and English by the bundle. For your own
fields add the keys to your project, e.g. `translations/admin.en.yaml`.

## Frontend

All fields, including your own, are available in Twig:

```twig
{% set articleConfig = article_config(uuid, template) %}

{% if articleConfig.readingSpeed > 0 %}
    ...
{% endif %}
```

`configSource` still tells where the values come from (`article`, `template_default`, `hardcoded`). `hardcoded`
means the defaults of the form. Only the fields of the form of the template are returned: values stored for fields
that were removed later are ignored and fields added later are filled with their default.

## Upgrading from 2.x

3.0 moves the values from fixed columns into the JSON column `data`. Copy the data **before** the old columns are
dropped, otherwise the old values are lost. Make a database backup first.

```bash
php bin/adminconsole sulu:article-configuration:migrate-to-json --dry-run
php bin/adminconsole sulu:article-configuration:migrate-to-json
php bin/adminconsole sulu:article-configuration:migrate-to-json --drop-legacy-columns
```

- The command adds the column `data` and copies the old columns into it. It can be run repeatedly: values already
  stored in `data` are never overwritten, only missing keys are filled.
- Columns of older bundle versions that this version does not know any more (e.g. `custom_data`) are copied to the
  `data` of each row under their camelCase name (`customData`) and reported with the number of rows that have a
  value. The key stays in `data` until the article is saved again, unless you define a field of that name in your XML.
- `--drop-legacy-columns` drops the old columns of `ar_article_configuration` after copying, it asks for confirmation
  (`--force` skips the question). Use it instead of `doctrine:schema:update --force`, which would also apply every
  other pending schema change of your project. `--dry-run` only lists what would happen.
- The Twig API and the names of the standard fields are unchanged. The former column default of `layoutStyle`
  (`default`) no longer exists, new articles use the default of the form (`fullwidth`).
