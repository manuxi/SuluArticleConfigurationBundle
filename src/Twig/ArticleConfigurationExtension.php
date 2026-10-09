<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Twig;

use Manuxi\SuluArticleConfigurationBundle\Service\ArticleConfigurationResolver;
use Manuxi\SuluArticleConfigurationBundle\Service\ArticleTemplateResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ArticleConfigurationExtension extends AbstractExtension
{
    public function __construct(
        private ArticleConfigurationResolver $resolver,
        private ArticleTemplateResolver $templateResolver,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('article_configuration', [$this, 'getConfiguration']),
            new TwigFunction('article_config', [$this, 'getConfiguration']),
            new TwigFunction('article_template_key', [$this, 'getTemplateKey']),
        ];
    }

    /**
     * Get resolved configuration for an article.
     *
     * Fallback chain:
     * 1. Article-specific config
     * 2. Template default (default=true)
     * 3. Defaults of the configured schema
     *
     * Usage:
     *   {% set config = article_configuration(article.id, article.templateKey) %}
     *   {{ config.layoutStyle }}
     *   {% if config.showToc %}...{% endif %}
     *
     * The returned array includes 'configSource' which can be:
     * - 'article': Config from this specific article
     * - 'template_default': Config from another article marked as default
     * - 'hardcoded': Defaults of the configured schema
     */
    public function getConfiguration(string $articleId, ?string $templateKey = null): array
    {
        return $this->resolver->resolve($articleId, $templateKey);
    }

    /**
     * Resolve the template key of an article that is only known by its frontend URL, e.g. an item coming from a
     * smart_content/selection list of a different content type (page, event, ...). Returns null if the URL does
     * not resolve to a live article.
     *
     * Usage:
     *   {% set templateKey = article_template_key(item.url, app.request.locale) %}
     */
    public function getTemplateKey(string $url, string $locale): ?string
    {
        return $this->templateResolver->resolveTemplateKey($url, $locale);
    }
}