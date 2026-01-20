# SuluArticleConfigurationBundle
![php workflow](https://github.com/manuxi/SuluArticleConfigurationBundle/actions/workflows/php.yml/badge.svg)
![symfony workflow](https://github.com/manuxi/SuluArticleConfigurationBundle/actions/workflows/symfony.yml/badge.svg)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/manuxi/SuluArticleConfigurationBundle/LICENSE)
![GitHub Tag](https://img.shields.io/github/v/tag/manuxi/SuluArticleConfigurationBundle)
![Supports Sulu 3.0 or later](https://img.shields.io/badge/%20Sulu->=3.0-0088cc?color=00b2df)

[🇬🇧 English Version](README.md)

Das **SuluArticleConfigurationBundle** erweitert Artikel in Sulu 3.0 um einen umfangreichen "Konfiguration"-Tab.
Es ermöglicht die Verwaltung zusätzlicher Darstellungsoptionen, Features und Veröffentlichungseinstellungen direkt am Artikel.

![img.png](docs/img/overview.png)

## ✨ Features

### 📋 Darstellungs-Optionen
- **Layout-Stil** - Wähle zwischen Standard, Breit, Volle Breite oder Schmal (Reading Mode)
- **Sidebar** - Sidebar aktivieren/deaktivieren und Position (Links/Rechts) bestimmen
- **Elemente anzeigen** - Inhaltsverzeichnis (TOC), Lesezeit, Autor-Box, Ähnliche Artikel

### ⚙️ Funktionen & Features
- **Interaktionen** - Kommentare, Share-Buttons
- **Tools** - Druck-Funktion, PDF-Download

### 🚀 Veröffentlichungs-Einstellungen
- **Highlighting** - "Hervorgehoben" (für Slider/Teaser)
- **Listen-Verhalten** - "Fixiert" (Sticky) oder "Aus Listen ausblenden" (nur direkt aufrufbar)
- **Metadaten** - Veröffentlichungsdatum ausblenden

### 🎨 Styling & Erweitert
- **Design** - Header Hintergrund- und Textfarbe, Custom CSS Klassen
- **Technik** - Custom Template Zuweisung, Cache Lifetime, Custom JSON Data

### 🔄 Standard-Konfiguration für Templates
- **Vererbungs-System** - Setze eine Standard-Konfiguration für alle Artikel desselben Templates
- **3-Stufen-Kaskade** - Artikel-spezifisch → Template-spezifisch → Standardwerte
- **Template-Erkennung** - Der Template-Name wird automatisch erkannt und gespeichert

## 📋 Voraussetzungen

- PHP 8.2 oder höher
- Sulu CMS 3.0 oder höher

## 👩🏻‍🏭 Installation

### Schritt 1: Paket installieren

Füge das Repository zu deiner `composer.json` hinzu (falls lokal) oder installiere es direkt:

```bash
composer require manuxi/sulu-article-configuration-bundle
```

Falls du *nicht* Symfony Flex verwendest, füge das Bundle in `config/bundles.php` hinzu:

```php
return [
    //...
    Manuxi\SuluArticleConfigurationBundle\SuluArticleConfigurationBundle::class => ['all' => true],
];
```

### Schritt 2: Routen konfigurieren

Füge Folgendes zu `config/routes.yaml` hinzu, um die Admin-API-Routen zu laden:

```yaml
sulu_article_configuration_api:
    resource: '@SuluArticleConfigurationBundle/Resources/config/routes_admin.yaml'
```

### Schritt 3: Datenbank aktualisieren

Erstelle die benötigte Tabelle `ar_article_configuration`:

```bash
# Prüfe was erstellt wird
php bin/console doctrine:schema:update --dump-sql

# Führe Migration aus
php bin/console doctrine:schema:update --force
```

## 🎣 Verwendung

### Admin-Oberfläche

1. Navigiere zu **Artikel** in der Sulu-Admin-Navigation.
2. Öffne einen bestehenden Artikel oder erstelle einen neuen (speichern!).
3. Klicke auf den **Konfiguration**-Tab.
4. Wähle die gewünschten Optionen aus (z.B. "Sidebar aktivieren", "Layout-Stil").
5. Speichere die Konfiguration.

### Standard-Konfiguration

Du kannst eine Standard-Konfiguration für alle Artikel mit demselben Template definieren:

1. Öffne einen Artikel mit dem gewünschten Template (z.B. "Blog-Beitrag")
2. Gehe zum **Konfiguration**-Tab
3. Konfiguriere alle Einstellungen wie gewünscht
4. Aktiviere **"Als Standard verwenden"** im Bereich "Standard-Konfiguration"
5. Speichere die Konfiguration.

Nun verwenden alle anderen Artikel mit diesem Template automatisch diese Einstellungen - es sei denn, sie haben eine eigene Konfiguration.

**So funktioniert die Kaskade:**

```
1. Artikel hat eigene Konfiguration? → wird verwendet
2. Es existiert eine Standard-Konfiguration für dieses Template? → wird verwendet
3. Keines von beiden? → hinterlegte Standardwerte werden verwendet
```

### Frontend-Nutzung (Twig)

Das Bundle stellt eine Twig-Funktion bereit, um die Konfiguration in Twig-Templates bereit zu stellen:

```twig
{# Konfiguration holen #}
{% set articleConfig = article_configuration(uuid, template) %}

{# Oder via Alias #}
{% set articleConfig = article_config(uuid, template) %}

{# Konfigurationswerte verwenden #}
<article class="article article--{{ articleConfig.layoutStyle }}{% if articleConfig.customCssClass %} {{ articleConfig.customCssClass }}{% endif %}">
    
    {% if articleConfig.showReadingTime %}
        <span class="reading-time">{{ reading_time }} Min. Lesezeit</span>
    {% endif %}
    
    {% if articleConfig.showToc %}
        <nav class="table-of-contents">
            {# ... TOC Inhalt ... #}
        </nav>
    {% endif %}
    
    <div class="article__content">
        {{ content|raw }}
    </div>
    
    {% if articleConfig.showAuthorBox %}
        <div class="author-box">
            {# ... Autor-Info ... #}
        </div>
    {% endif %}
    
    {% if articleConfig.showRelated %}
        <section class="related-articles">
            {# ... Ähnliche Artikel ... #}
        </section>
    {% endif %}
    
    {% if articleConfig.enableShareButtons %}
        <div class="share-buttons">
            {# ... Share-Buttons ... #}
        </div>
    {% endif %}
    
</article>

{# Prüfen, woher die Config kommt #}
{% if articleConfig.configSource == 'template_default' %}
    <!-- Verwendet Konfiguration aus Artikel {{ articleConfig.templateDefaultArticleId }} -->
{% endif %}
```

**Verfügbare Konfigurationswerte:**

| Eigenschaft | Typ | Standard | Beschreibung |
|-------------|-----|----------|--------------|
| `layoutStyle` | string | `'default'` | `default`, `wide`, `fullwidth`, `narrow` |
| `enableSidebar` | bool | `true` | Sidebar anzeigen |
| `sidebarPosition` | string | `'right'` | `left`, `right` |
| `showToc` | bool | `true` | Inhaltsverzeichnis anzeigen |
| `showReadingTime` | bool | `true` | Lesezeit anzeigen |
| `showAuthorBox` | bool | `true` | Autor-Box anzeigen |
| `showRelated` | bool | `true` | Ähnliche Artikel anzeigen |
| `enableComments` | bool | `false` | Kommentare aktivieren |
| `enableShareButtons` | bool | `true` | Teilen-Buttons anzeigen |
| `enablePrint` | bool | `true` | Drucken-Button anzeigen |
| `enableDownloadPdf` | bool | `false` | PDF-Download anzeigen |
| `isFeatured` | bool | `false` | Hervorgehobener Artikel |
| `isSticky` | bool | `false` | In Listen fixiert |
| `hideFromLists` | bool | `false` | Aus Listen ausblenden |
| `hidePublishDate` | bool | `false` | Veröffentlichungsdatum verbergen |
| `customCssClass` | string | `null` | Eigene CSS-Klasse |
| `headerBgColor` | string | `null` | Header Hintergrundfarbe |
| `headerTextColor` | string | `'auto'` | `auto`, `light`, `dark` |
| `customTemplate` | string | `null` | Pfad zum eigenen Template |
| `cacheLifetime` | int | `86400` | Cache-Lebensdauer in Sekunden |
| `customData` | string | `null` | Eigene JSON-Daten |
| `configSource` | string | - | `article`, `template_default`, `hardcoded` |

**Beispiel: Bedingtes Sidebar-Layout**

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

**Beispiel: Eigenes Header-Styling**

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

## 🗄️ Datenbank-Schema

Das Bundle erstellt folgende Tabelle:

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

## 🧪 Tests

```bash
composer test
```

## 📄 Lizenz

Dieses Bundle steht unter der MIT-Lizenz. Die vollständige Lizenz findest du im Bundle: [LICENSE](LICENSE)

## 👤 Autor

**Manuel Bertrams**
- GitHub: [@manuxi](https://github.com/manuxi)