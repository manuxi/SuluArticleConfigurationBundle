# Changelog

## 3.0.0

### Breaking changes
- The values of an article are stored as JSON in the new column `data` of `ar_article_configuration`. The columns
  `layout_style`, `enable_sidebar`, `sidebar_position`, `show_toc`, `show_reading_time`, `show_author_box`,
  `show_related`, `enable_share_buttons`, `enable_print`, `hide_publish_date` and `custom_css_class` are dropped. Run
  `sulu:article-configuration:migrate-to-json --drop-legacy-columns` (or the command without the option before
  `doctrine:schema:update --force`), see [docs/configuration.en.md](docs/configuration.en.md).
- The admin form key `article_configuration` is no longer loaded by the tab. Each template gets its own composed form
  `article_configuration_template_<templateKey>`; `article_configuration` is now the base level of that composition.
- `ArticleConfigurationAdmin` takes `ArticleGroupProvider` instead of `GroupProviderInterface`.
- `ArticleConfigurationResolver` and `ArticleConfigurationController` take `ConfigurationSchema` as an additional argument.
- `ArticleConfiguration` loses the typed getters/setters of the former columns, use `getData()`/`setData()`.
- The `layoutStyle` column default `default` is gone, new articles use the form default `fullwidth`.

### Added
- Configuration with Sulu form XML on three levels: base (`article_configuration`, shipped), article group
  (`article_configuration_group_<group>`) and template (`article_configuration_template_<templateKey>`).
  Files in `config/article_configuration/` are registered automatically; group and template files only contain the
  differences: new fields and sections, replaced fields and fields removed with `<tag name="article_configuration.remove"/>`.
- One "Configuration" tab per template, shown by the tab condition `template == '<templateKey>'`.
- `ArticleConfigurationFormComposer` composes the form of a template; `ArticleConfigurationFormMetadataLoader`
  serves it to the admin (tag `sulu_admin.form_metadata_loader`) including the validation schema of the composed fields.
- `ConfigurationSchema` and `FieldDefinition` derive the typed fields from the composed form: `checkbox` as bool,
  `single_select`/`select` restricted to their values, `number`, text types as string, any other type (e.g.
  `media_selection`) stored as delivered. Values from the admin are validated against it.
- `ArticleGroupProvider` resolves the article group of a template and keeps the `getGroups` compatibility for Sulu
  before and after 3.0.9.
- Console command `sulu:article-configuration:migrate-to-json`:
  - `--dry-run` only reports.
  - Repeatable: values already stored in `data` win, only missing keys are filled from the old columns.
  - Columns unknown to this version (e.g. `custom_data` of older bundle versions) are copied to `data` under their camelCase
    name and reported with the number of rows that have a value.
  - `--drop-legacy-columns` drops the old columns afterwards (asks first, `--force` skips the question), so no
    project-wide `doctrine:schema:update --force` is needed for this table.
- Documentation: `docs/configuration.en.md`, `docs/configuration.de.md`.

### Changed
- Doctrine mapping moved from PHP attributes to `Resources/config/doctrine/ArticleConfiguration.orm.xml`.
- The values returned by `article_configuration()`/`article_config()` contain only the fields of the form of the
  template; missing values are filled with the form default. `configSource: hardcoded` now means the form defaults.

### Fixed
- Compatibility with all Sulu 3.0 releases: `FieldMetadata::hasTag()` (missing in Sulu 3.0.3) and the internal
  `getFlatFieldMetadata()`/`findOption()` are replaced by `MetadataReader`, which only uses long-standing getters.
  Before the fix the "Configuration" tab and `article_config()` failed with "Call to undefined method
  FieldMetadata::hasTag()".
- The dry run of the migration command says "Would add column" instead of "Adding column".
- CI runs the suite against Sulu 3.0.0 and the latest 3.0 release (matrix in both workflows); the suite was also run
  against 3.0.3 and 3.0.10.
- CI: `phpunit/phpunit` is required as `^9.6` instead of the exact version 9.6.0, which Composer blocks because of a
  security advisory; the test step of the PHP workflow is enabled.

## 2.0.0
- PDF switches (`enableDownloadPdf`, `pdfShowCaptions`, `pdfShowAuthor`, `pdfShowModified`, `pdfShowOnlineLink`,
  `pdfCompanyData`) removed, they live in the excerpt tab of manuxi/sulu-pdf-bundle.

## 1.4.0
- PDF options (captions, author box, modified date, online link, company data).

## 1.3.0
- PDF download activated, nine unused fields dropped, default layout `fullwidth`.

## 1.2.0
- `article_template_key(url, locale)` Twig function resolves the template key of an article from its frontend URL.

## 1.1.1
- Support for `GroupProvider::getGroups(string $key)` of Sulu 3.0.9.

## 1.1.0
- Fallback chain article configuration, template configuration, default configuration; Twig extension.

## 1.0.0
- Initial release: "Configuration" tab for Sulu 3.0 articles.
