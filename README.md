# SuluArticleConfigurationBundle
![php workflow](https://github.com/manuxi/SuluArticleConfigurationBundle/actions/workflows/php.yml/badge.svg)
![symfony workflow](https://github.com/manuxi/SuluArticleConfigurationBundle/actions/workflows/symfony.yml/badge.svg)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/manuxi/SuluArticleConfigurationBundle/LICENSE)
![GitHub Tag](https://img.shields.io/github/v/tag/manuxi/SuluArticleConfigurationBundle)
![Supports Sulu 3.0 or later](https://img.shields.io/badge/%20Sulu->=3.0-0088cc?color=00b2df)

[🇩🇪 German Version](README.de.md)

The **SuluArticleConfigurationBundle** extends Sulu 3.0 Articles with a comprehensive "Configuration" tab.
It allows managing additional display options, features, and publication settings directly on the article.

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

## 📋 Prerequisites

- PHP 8.2 or higher
- Sulu CMS 3.0 or higher

## 👩🏻‍🏭 Installation

### Step 1: Install the package

Add the repository to your `composer.json` (if local) or install directly:

```bash
composer require sulu/article-configuration-bundle
```

If you are *not* using Symfony Flex, add the bundle to `config/bundles.php`:

```php
return [
    //...
    Sulu\Bundle\ArticleConfigurationBundle\SuluArticleConfigurationBundle::class => ['all' => true],
];
```

### Step 2: Configure routes

Add the following to `config/routes.yaml` to load the Admin API routes:

```yaml
sulu_article_configuration_api:
    resource: '@SuluArticleConfigurationBundle/Resources/config/routes_admin.yaml'
    prefix: /
```

### Step 3: Update the database

Create the required `article_configuration` table:

```bash
# Check what will be created
php bin/console doctrine:schema:update --dump-sql

# Execute migration
php bin/console doctrine:schema:update --force
```

## 🎣 Usage

1. Navigate to **Articles** in the Sulu admin navigation.
2. Open an existing article or create a new one.
3. Click on the **Configuration** tab.
4. Select the desired options (e.g., "Enable Sidebar", "Layout Style").
5. Save the article.

Configuration values are stored in a separate table and linked to the article.

## 📝 License

This bundle is licensed under the MIT License. See [LICENSE](LICENSE).
