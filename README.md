# SuluArticleConfigurationBundle
![php workflow](https://github.com/manuxi/SuluArticleConfigurationBundle/actions/workflows/php.yml/badge.svg)
![symfony workflow](https://github.com/manuxi/SuluArticleConfigurationBundle/actions/workflows/symfony.yml/badge.svg)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/manuxi/SuluArticleConfigurationBundle/LICENSE)
![GitHub Tag](https://img.shields.io/github/v/tag/manuxi/SuluArticleConfigurationBundle)
![Supports Sulu 3.0 or later](https://img.shields.io/badge/%20Sulu->=3.0-0088cc?color=00b2df)

[🇩🇪 German Version](README.de.md)

The **SuluArticleConfigurationBundle** extends Sulu 3.0 Articles with a comprehensive "Configuration" tab.
It allows managing additional display options, features, and publication settings directly on the article.

![img.png](docs/img/overview.png)

## ✨ Features

### 📋 Display Options
- **Layout Style** - Choose between Default, Wide, Full Width, or Narrow (Reading Mode)
- **Sidebar** - Enable/disable sidebar and set position (Left/Right)
- **Show Elements** - Table of Contents (TOC), Reading Time, Author Box, Related Articles

### ⚙️ Functions & Features
- **Interactions** - Share Buttons
- **Tools** - Print Function

### 🚀 Publication Settings
- **Metadata** - Hide Publish Date

### 🎨 Styling & Advanced
- **Design** - Custom CSS classes

### 🧩 Configurable Fields
- **Sulu form XML** - Add, change or remove fields with plain Sulu form XML in your project, no database change needed
- **Per group and per template** - Every template can have its own set of fields, see [docs/configuration.en.md](docs/configuration.en.md)

### 🔄 Default-Configuration
- **Inheritance System** - Set a configuration as default for all articles of the same template key
- **3-Tier Cascade** - Article-specific → Template key specific → default values
- **Automatic Template Detection** - The template key is automatically detected and stored

## 📋 Prerequisites

- PHP 8.2 or higher
- Sulu CMS 3.0 or higher

## 👩🏻‍🏭 Installation

### Step 1: Install the package

Add the repository to your `composer.json` (if local) or install directly:

```bash
composer require manuxi/sulu-article-configuration-bundle
```

If you are *not* using Symfony Flex, add the bundle to `config/bundles.php`:

```php
return [
    //...
    Manuxi\SuluArticleConfigurationBundle\SuluArticleConfigurationBundle::class => ['all' => true],
];
```

### Step 2: Configure routes

Add the following to `config/routes.yaml` to load the Admin API routes:

```yaml
sulu_article_configuration_api:
    resource: '@SuluArticleConfigurationBundle/Resources/config/routes_admin.yaml'
```

### Step 3: Update the database

Create the required `ar_article_configuration` table:

```bash
# Check what will be created
php bin/console doctrine:schema:update --dump-sql

# Execute migration
php bin/console doctrine:schema:update --force
```

## 🎣 Usage

### Admin Interface

1. Navigate to **Articles** in the Sulu admin navigation.
2. Open an existing article or create a new one.
3. Click on the **Configuration** tab.
4. Select the desired options (e.g., "Enable Sidebar", "Layout Style").
5. Save the config.

### Template Defaults

You can set a configuration as default for all articles with the same template:

1. Open an article with the desired template (e.g., "Blog Post").
2. Go to the **Configuration** tab.
3. Configure all settings as desired.
4. Enable **"Use as default"** in the "Default Configuration" section.
5. Save the config.

Now all other articles with this template will automatically use these settings - unless they have their own configuration.

**How the cascade works:**

```
1. Article has own configuration? → Use it
2. Default config for that template exists? → Use it
3. Neither? → Use default values
```

### Custom Fields

The fields of the "Configuration" tab are defined with Sulu form XML. The bundle ships the fields listed below; your
project can change them or add its own - for all articles, per article group or per template. Drop the files into
`config/article_configuration/` (registered automatically):

```
config/article_configuration/
    group_blog.xml            <key>article_configuration_group_blog</key>
    template_blog_post.xml    <key>article_configuration_template_blog_post</key>
```

The files only contain the differences to the level before. A new `<property>` adds a field, a property with the
same name replaces it, and `<tag name="article_configuration.remove"/>` removes it:

```xml
<section name="display_options">
    <properties>
        <property name="showToc" type="checkbox">
            <tag name="article_configuration.remove"/>
        </property>
    </properties>
</section>
```

Values are stored as JSON, so a new field never needs a schema update. See [docs/configuration.en.md](docs/configuration.en.md)
for the levels, value types, translations and a complete example.

### Frontend Usage (Twig)

The bundle provides a Twig function to access the resolved configuration in your twig templates:

```twig
{# Get configuration #}
{% set articleConfig = article_configuration(uuid, template) %}

{# Or use shortcut/alias #}
{% set articleConfig = article_config(uuid, template) %}

{# Use the configuration values #}
<article class="article article--{{ articleConfig.layoutStyle }}{% if articleConfig.customCssClass %} {{ articleConfig.customCssClass }}{% endif %}">
    
    {% if articleConfig.showReadingTime %}
        <span class="reading-time">{{ reading_time }} min read</span>
    {% endif %}
    
    {% if articleConfig.showToc %}
        <nav class="table-of-contents">
            {# ... TOC content ... #}
        </nav>
    {% endif %}
    
    <div class="article__content">
        {{ content|raw }}
    </div>
    
    {% if articleConfig.showAuthorBox %}
        <div class="author-box">
            {# ... Author info ... #}
        </div>
    {% endif %}
    
    {% if articleConfig.showRelated %}
        <section class="related-articles">
            {# ... Related articles ... #}
        </section>
    {% endif %}
    
    {% if articleConfig.enableShareButtons %}
        <div class="share-buttons">
            {# ... Share buttons ... #}
        </div>
    {% endif %}
    
</article>

{# Check where the config came from #}
{% if articleConfig.configSource == 'template_default' %}
    <!-- Using template default from article {{ articleConfig.templateDefaultArticleId }} -->
{% endif %}
```

**Available configuration values** (the fields shipped with the bundle; your schema may add or remove fields, and `hardcoded` means the schema defaults):

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `layoutStyle` | string | `'fullwidth'` | `default`, `wide`, `fullwidth`, `narrow` |
| `enableSidebar` | bool | `true` | Show sidebar |
| `sidebarPosition` | string | `'right'` | `left`, `right` |
| `showToc` | bool | `true` | Show table of contents |
| `showReadingTime` | bool | `true` | Show reading time |
| `showAuthorBox` | bool | `true` | Show author box |
| `showRelated` | bool | `true` | Show related articles |
| `enableShareButtons` | bool | `true` | Show share buttons |
| `enablePrint` | bool | `true` | Show print button |
| `hidePublishDate` | bool | `false` | Hide publish date |
| `customCssClass` | string | `null` | Custom CSS class |
| `configSource` | string | - | `article`, `template_default`, `hardcoded` |

**Example: Conditional sidebar layout**

```twig
{% set articleConfig = article_config(uuid, template) %}

<div class="layout layout--{{ articleConfig.layoutStyle }}">
    {% if articleConfig.enableSidebar %}
        <div class="layout__sidebar layout__sidebar--{{ articleConfig.sidebarPosition }}">
            {% if articleConfig.showToc %}
                {{ render_toc(content) }}
            {% endif %}
        </div>
    {% endif %}
    
    <main class="layout__main">
        {{ content|raw }}
    </main>
</div>
```

**Example: Custom CSS class**

```twig
{% set articleConfig = article_configuration(article.id, article.templateKey) %}

<header class="article-header{% if articleConfig.customCssClass %} {{ articleConfig.customCssClass }}{% endif %}">
    <h1>{{ article.title }}</h1>
</header>
```

### Resolving an Article's Template by URL

`article_configuration`/`article_config` need the article's own `templateKey`, which is trivial to pass when you
are already rendering that article's own page. It is not reachable through Sulu's `properties` mapping on a
`smart_content`/selection list, though (the field lives in the resource's internal "view" data, which
`recursivelyMapProperties()` never maps) -- for example an "articles" collection field on a page or a different
article, where each item is only a title/subtitle/url/image, would always miss it.

`article_template_key(url, locale)` resolves it from the article's frontend URL instead (via the route table),
so it works from exactly that situation. Returns `null` if the URL does not resolve to a live article.

```twig
{% set templateKey = article_template_key(item.url, app.request.locale) %}
{% if templateKey %}
    <span class="badge">{{ templateKey }}</span>
{% endif %}
```

Combine with your own template-to-color/label mapping (e.g. a project-level color palette) to badge articles
by their template in a list -- the bundle only resolves the key, it has no opinion on colors or labels.

## ⬆️ Upgrading from 2.x

3.0 stores the values as JSON instead of fixed columns. Make a database backup, then copy the data **before** the old
columns are dropped:

```bash
php bin/adminconsole sulu:article-configuration:migrate-to-json --dry-run
php bin/adminconsole sulu:article-configuration:migrate-to-json
php bin/adminconsole sulu:article-configuration:migrate-to-json --drop-legacy-columns
```

Details and the list of breaking changes: [docs/configuration.en.md](docs/configuration.en.md) and [CHANGELOG.md](CHANGELOG.md).

## ⬆️ Upgrading from 1.x

2.0 removes the PDF switches (`enableDownloadPdf`, `pdfShowCaptions`, `pdfShowAuthor`, `pdfShowModified`, `pdfShowOnlineLink`, `pdfCompanyData`) from the "Configuration" tab. They now live in the **excerpt tab** of the article, provided by [manuxi/sulu-pdf-bundle](https://github.com/manuxi/SuluPdfBundle) (1.3+) - per language and with the draft/publish workflow:

```yaml
sulu_pdf:
    excerpt:
        articles: true
```

Replace `articleConfig.enableDownloadPdf` in your templates with `sulu_pdf_available('articles', uuid, app.request.locale)`, run `bin/adminconsole doctrine:schema:update --force` to drop the columns and set the switches again in the excerpt tab.

## 🗄️ Database Schema

The bundle creates the following table:

```sql
CREATE TABLE ar_article_configuration (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id VARCHAR(36) UNIQUE NOT NULL,
    template_key VARCHAR(128) DEFAULT NULL,
    is_default TINYINT(1) DEFAULT 0 NOT NULL,
    data JSON DEFAULT NULL,
    INDEX idx_template_default (template_key, is_default)
);
```

## 🧪 Testing

```bash
composer test
```

## 📄 License

This bundle is under the MIT license. See the complete license in the bundle: [LICENSE](LICENSE)

## 👤 Author

**Manuel Bertrams**
- GitHub: [@manuxi](https://github.com/manuxi)