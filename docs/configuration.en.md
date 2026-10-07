# Configuring the fields

[🇩🇪 Deutsche Version](configuration.de.md)

The fields of the "Configuration" tab are defined by a **schema** in your project configuration. The values of an
article are stored as JSON in the column `data` of `ar_article_configuration`, so adding or removing a field never
needs a database change.

## Levels

The schema of an article is resolved from four levels. A later level overrides the earlier one:

| Level | Config key | Applies to |
|-------|------------|------------|
| 1. Built-in | - | all articles (the 11 fields shipped with the bundle) |
| 2. Default | `default` | all articles |
| 3. Group | `groups.<group>` | all templates of an article group (the `<group>` of the template XML) |
| 4. Template | `templates.<templateKey>` | a single template |

The Sulu admin shows exactly one "Configuration" tab per article, built from the schema of the template the article
uses. Changing the template of an article and saving it switches the tab.

## Configuration

`config/packages/sulu_article_configuration.yaml`:

```yaml
sulu_article_configuration:
    default:
        fields:
            layoutStyle:
                default: narrow              # change the default value of a built-in field
            customCssClass: false            # remove a field everywhere
    groups:
        blog:
            fields:
                heroVariant:                 # add a field for all templates of the group "blog"
                    type: single_select
                    values: [image, video]
                    default: image
                    section: hero
    templates:
        blog_post:
            fields:
                showToc: false               # remove a field for this template only
                layoutStyle:
                    values: [narrow, wide]   # change the options of a field for this template only
                    default: narrow
                readingSpeed:
                    type: number
                    default: 200
```

Overriding a field merges the given options into the existing definition, only `values` is replaced as a whole.
`false` removes the field. A field that does not exist yet needs at least a `type`.

After changing the schema, clear the cache: `bin/console cache:clear` (Sulu caches the form metadata).

## Field options

| Option | Description |
|--------|-------------|
| `type` | `toggle` (bool), `text` (string or null), `single_select` (one of `values`), `number` (int or float) |
| `default` | Default value. `toggle` defaults to `false`, `single_select` to the first value, the others to `null` |
| `values` | List of allowed values, required for `single_select` |
| `section` | Name of the section (group box) in the form. Fields without a section go to "Additional Options" |
| `colspan` | Width in the 12 column grid, e.g. `6` |
| `visible_condition` | Sulu condition on the other fields of the form, e.g. `enableSidebar == true` |

Values sent by the admin are validated against the schema: unknown keys are dropped, wrong types fall back to the
default, a `single_select` only accepts its `values`.

## Translations

Labels are translation keys in the `admin` domain, derived from the field name in snake_case. Add them to your
project, e.g. `translations/admin.en.yaml`:

```yaml
sulu_article_configuration:
    hero: "Hero"                      # label of the section "hero"
    hero_variant:                     # field "heroVariant"
        title: "Hero variant"
        info: "Image or video in the article header."
        image: "Image"                # one key per value of a select
        video: "Video"
```

The fields shipped with the bundle are already translated (German and English). Missing keys are shown as the
plain key.

## Frontend

All fields, including your own, are available in Twig:

```twig
{% set articleConfig = article_config(uuid, template) %}

{% if articleConfig.heroVariant == 'video' %}
    ...
{% endif %}
```

`configSource` still tells where the values come from (`article`, `template_default`, `hardcoded`). `hardcoded`
means the defaults of the schema. Only the fields of the template's schema are returned, values stored for fields
that were removed later are ignored and fields added later are filled with their default.

## Upgrading from 2.x

3.0 moves the values from fixed columns into the JSON column `data`. Run the data migration **before** the schema
update, otherwise the old values are lost:

```bash
php bin/console sulu:article-configuration:migrate-to-json --dry-run
php bin/console sulu:article-configuration:migrate-to-json
php bin/console doctrine:schema:update --force
```

The command adds the column `data`, copies the old columns into it and can be run repeatedly. The schema update
afterwards drops the old columns. The Twig API and the field names are unchanged, a bundle default of the previous
`layoutStyle` column (`default`) no longer exists, new articles use the schema default (`fullwidth`).
