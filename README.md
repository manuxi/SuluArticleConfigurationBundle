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
- **Interactions** - Comments, Share Buttons
- **Tools** - Print Function, PDF Download

### 🚀 Publication Settings
- **Highlighting** - "Featured" (for sliders/teasers)
- **List Behavior** - "Sticky" (fixed at top) or "Hide from Lists" (accessible via direct link only)
- **Metadata** - Hide Publish Date

### 🎨 Styling & Advanced
- **Design** - Header background and text color, Custom CSS classes
- **Technical** - Custom Template assignment, Cache Lifetime, Custom JSON Data

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

**Available configuration values:**

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `layoutStyle` | string | `'default'` | `default`, `wide`, `fullwidth`, `narrow` |
| `enableSidebar` | bool | `true` | Show sidebar |
| `sidebarPosition` | string | `'right'` | `left`, `right` |
| `showToc` | bool | `true` | Show table of contents |
| `showReadingTime` | bool | `true` | Show reading time |
| `showAuthorBox` | bool | `true` | Show author box |
| `showRelated` | bool | `true` | Show related articles |
| `enableComments` | bool | `false` | Enable comments |
| `enableShareButtons` | bool | `true` | Show share buttons |
| `enablePrint` | bool | `true` | Show print button |
| `enableDownloadPdf` | bool | `false` | Show PDF download |
| `isFeatured` | bool | `false` | Featured article |
| `isSticky` | bool | `false` | Sticky in lists |
| `hideFromLists` | bool | `false` | Hide from lists |
| `hidePublishDate` | bool | `false` | Hide publish date |
| `customCssClass` | string | `null` | Custom CSS class |
| `headerBgColor` | string | `null` | Header background color |
| `headerTextColor` | string | `'auto'` | `auto`, `light`, `dark` |
| `customTemplate` | string | `null` | Custom template path |
| `cacheLifetime` | int | `86400` | Cache lifetime in seconds |
| `customData` | string | `null` | Custom JSON data |
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

**Example: Custom header styling**

```twig
{% set config = article_configuration(article.id, article.templateKey) %}

<header class="article-header" 
    {% if articleConfig.headerBgColor %}
        style="background-color: {{ articleConfig.headerBgColor }}; 
               color: {% if articleConfig.headerTextColor == 'light' %}#fff{% elseif articleConfig.headerTextColor == 'dark' %}#000{% else %}inherit{% endif %};"
    {% endif %}>
    <h1>{{ article.title }}</h1>
</header>
```

## 🗄️ Database Schema

The bundle creates the following table:

```sql
CREATE TABLE ar_article_configuration (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id VARCHAR(36) UNIQUE NOT NULL,
    template_key VARCHAR(128) DEFAULT NULL,
    is_default TINYINT(1) DEFAULT 0 NOT NULL,
    layout_style VARCHAR(32) DEFAULT 'default' NOT NULL,
    enable_sidebar TINYINT(1) DEFAULT 1 NOT NULL,
    sidebar_position VARCHAR(16) DEFAULT 'right' NOT NULL,
    show_toc TINYINT(1) DEFAULT 1 NOT NULL,
    show_reading_time TINYINT(1) DEFAULT 1 NOT NULL,
    show_author_box TINYINT(1) DEFAULT 1 NOT NULL,
    show_related TINYINT(1) DEFAULT 1 NOT NULL,
    enable_comments TINYINT(1) DEFAULT 0 NOT NULL,
    enable_share_buttons TINYINT(1) DEFAULT 1 NOT NULL,
    enable_print TINYINT(1) DEFAULT 1 NOT NULL,
    enable_download_pdf TINYINT(1) DEFAULT 0 NOT NULL,
    is_featured TINYINT(1) DEFAULT 0 NOT NULL,
    is_sticky TINYINT(1) DEFAULT 0 NOT NULL,
    hide_from_lists TINYINT(1) DEFAULT 0 NOT NULL,
    hide_publish_date TINYINT(1) DEFAULT 0 NOT NULL,
    custom_css_class VARCHAR(128) DEFAULT NULL,
    header_bg_color VARCHAR(32) DEFAULT NULL,
    header_text_color VARCHAR(16) DEFAULT 'auto' NOT NULL,
    custom_template VARCHAR(255) DEFAULT NULL,
    cache_lifetime INT DEFAULT 86400 NOT NULL,
    custom_data LONGTEXT DEFAULT NULL,
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