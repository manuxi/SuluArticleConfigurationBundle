<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Admin\FormMetadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataLoaderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;

/**
 * Serves the composed form "article_configuration_template_<templateKey>" of the "Configuration" tab.
 */
class ArticleConfigurationFormMetadataLoader implements FormMetadataLoaderInterface
{
    public function __construct(
        private readonly ArticleConfigurationFormComposer $composer,
    ) {
    }

    public static function getFormKey(string $templateKey): string
    {
        return ArticleConfigurationFormComposer::TEMPLATE_KEY_PREFIX . $templateKey;
    }

    public function getMetadata(string $key, string $locale, array $metadataOptions): ?MetadataInterface
    {
        $prefix = ArticleConfigurationFormComposer::TEMPLATE_KEY_PREFIX;
        if (!\str_starts_with($key, $prefix) || \strlen($key) === \strlen($prefix)) {
            return null;
        }

        return $this->composer->compose(\substr($key, \strlen($prefix)), $locale);
    }
}
