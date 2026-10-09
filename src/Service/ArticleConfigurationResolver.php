<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Service;

use Manuxi\SuluArticleConfigurationBundle\Entity\ArticleConfiguration;
use Manuxi\SuluArticleConfigurationBundle\Repository\ArticleConfigurationRepository;
use Manuxi\SuluArticleConfigurationBundle\Schema\ConfigurationSchema;

class ArticleConfigurationResolver
{
    public function __construct(
        private ArticleConfigurationRepository $repository,
        private ConfigurationSchema $schema,
    ) {
    }

    /**
     * Resolve configuration with fallback chain:
     * 1. Article-specific config (if exists)
     * 2. Template default (default=true for this templateKey)
     * 3. Defaults of the configured schema
     *
     * @return array<string, mixed>
     */
    public function resolve(string $articleId, ?string $templateKey = null): array
    {
        $articleConfig = $this->repository->findByArticleId($articleId);
        if ($articleConfig) {
            $result = $this->entityToArray($articleConfig, $templateKey);
            $result['configSource'] = 'article';

            return $result;
        }

        if ($templateKey) {
            $templateDefault = $this->repository->findDefaultForTemplate($templateKey);
            if ($templateDefault) {
                $result = $this->entityToArray($templateDefault, $templateKey);
                $result['configSource'] = 'template_default';
                $result['templateDefaultArticleId'] = $templateDefault->getArticleId();

                return $result;
            }
        }

        $result = $this->schema->getDefaults($templateKey);
        $result['default'] = false;
        $result['templateKey'] = $templateKey;
        $result['configSource'] = 'hardcoded';

        return $result;
    }

    public function getForArticle(string $articleId): ?ArticleConfiguration
    {
        return $this->repository->findByArticleId($articleId);
    }

    public function getTemplateDefault(string $templateKey): ?ArticleConfiguration
    {
        return $this->repository->findDefaultForTemplate($templateKey);
    }

    /**
     * @return array<string, mixed>
     */
    private function entityToArray(ArticleConfiguration $entity, ?string $templateKey): array
    {
        $schemaTemplateKey = $templateKey ?? $entity->getTemplateKey();

        return \array_merge(
            [
                'articleId' => $entity->getArticleId(),
                'templateKey' => $entity->getTemplateKey(),
                'default' => $entity->isDefault(),
            ],
            $this->schema->sanitize($schemaTemplateKey, $entity->getData())
        );
    }
}
