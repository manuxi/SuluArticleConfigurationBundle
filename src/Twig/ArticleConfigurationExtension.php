<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Twig;

use Manuxi\SuluArticleConfigurationBundle\Service\ArticleConfigurationResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ArticleConfigurationExtension extends AbstractExtension
{
    public function __construct(
        private ArticleConfigurationResolver $resolver,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('article_configuration', [$this, 'getConfiguration']),
            new TwigFunction('article_config', [$this, 'getConfiguration']),
        ];
    }

    /**
     * Get resolved configuration for an article.
     *
     * Fallback chain:
     * 1. Article-specific config
     * 2. Template default (default=true)
     * 3. Hardcoded defaults
     *
     * Usage:
     *   {% set config = article_configuration(article.id, article.templateKey) %}
     *   {{ config.layoutStyle }}
     *   {% if config.showToc %}...{% endif %}
     *
     * The returned array includes 'configSource' which can be:
     * - 'article': Config from this specific article
     * - 'template_default': Config from another article marked as default
     * - 'hardcoded': Built-in defaults
     */
    public function getConfiguration(string $articleId, ?string $templateKey = null): array
    {
        return $this->resolver->resolve($articleId, $templateKey);
    }
}