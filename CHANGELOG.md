# Changelog

## 3.0.0

### Breaking changes
- The values of an article are stored as JSON in the new column `data` of `ar_article_configuration`. The columns
  `layout_style`, `enable_sidebar`, `sidebar_position`, `show_toc`, `show_reading_time`, `show_author_box`,
  `show_related`, `enable_share_buttons`, `enable_print`, `hide_publish_date` and `custom_css_class` are dropped. Run
  `sulu:article-configuration:migrate-to-json` before `doctrine:schema:update --force`, see
  [docs/configuration.en.md](docs/configuration.en.md).
- The form key `article_configuration` is replaced by one generated form per template (`article_configuration_<templateKey>`).
  `forms/article_configuration.xml` is removed.
- `ArticleConfigurationAdmin` takes `ArticleGroupProvider` instead of `GroupProviderInterface`.
- `ArticleConfigurationResolver` and `ArticleConfigurationController` take `ConfigurationSchema` as an additional argument.
- `ArticleConfiguration` loses the typed getters/setters of the former columns, use `getData()`/`setData()`.
- The `layoutStyle` column default `default` is gone, new articles use the schema default `fullwidth`.

### Added
- YAML schema `sulu_article_configuration` with the levels `default`, `groups.<group>` and `templates.<templateKey>`:
  add, change and remove fields; types `toggle`, `text`, `single_select`, `number`.
- One "Configuration" tab per template, shown by the tab condition `template == '<templateKey>'`.
- `ArticleConfigurationFormMetadataLoader` builds the admin form from the schema (tag `sulu_admin.form_metadata_loader`).
- `ConfigurationSchema`, `FieldDefinition` (validation and casting of values) and `ArticleGroupProvider`
  (resolves the group of a template, keeps the `getGroups` compatibility for Sulu before and after 3.0.9).
- Console command `sulu:article-configuration:migrate-to-json` with `--dry-run`.
- Translation key `sulu_article_configuration.custom_options` (section of fields without a section).
- Documentation: `docs/configuration.en.md`, `docs/configuration.de.md`.

### Changed
- Doctrine mapping moved from PHP attributes to `Resources/config/doctrine/ArticleConfiguration.orm.xml`.
- The values returned by `article_configuration()`/`article_config()` contain only the fields of the schema of the
  template; missing values are filled with the schema default. `configSource: hardcoded` now means the schema defaults.
- Values sent by the admin are validated against the schema (unknown keys dropped, wrong types fall back to the default).

### Fixed
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
