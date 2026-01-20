<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Service;

use Manuxi\SuluArticleConfigurationBundle\Entity\ArticleConfiguration;
use Manuxi\SuluArticleConfigurationBundle\Repository\ArticleConfigurationRepository;

class ArticleConfigurationResolver
{
    public function __construct(
        private ArticleConfigurationRepository $repository,
    ) {
    }

    /**
     * Resolve configuration with fallback chain:
     * 1. Article-specific config (if exists)
     * 2. Template default (default=true for this templateKey)
     * 3. Hardcoded defaults
     */
    public function resolve(string $articleId, ?string $templateKey = null): array
    {
        $articleConfig = $this->repository->findByArticleId($articleId);
        if ($articleConfig) {
            $result = $this->entityToArray($articleConfig);
            $result['configSource'] = 'article';
            return $result;
        }

        if ($templateKey) {
            $templateDefault = $this->repository->findDefaultForTemplate($templateKey);
            if ($templateDefault) {
                $result = $this->entityToArray($templateDefault);
                $result['configSource'] = 'template_default';
                $result['templateDefaultArticleId'] = $templateDefault->getArticleId();
                return $result;
            }
        }

        $result = $this->getHardcodedDefaults();
        $result['configSource'] = 'hardcoded';
        $result['templateKey'] = $templateKey;
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

    private function getHardcodedDefaults(): array
    {
        return [
            'layoutStyle' => 'default',
            'showToc' => true,
            'showReadingTime' => true,
            'showAuthorBox' => true,
            'showRelated' => true,
            'enableSidebar' => true,
            'sidebarPosition' => 'right',
            'enableComments' => false,
            'enableShareButtons' => true,
            'enablePrint' => true,
            'enableDownloadPdf' => false,
            'isFeatured' => false,
            'isSticky' => false,
            'hideFromLists' => false,
            'hidePublishDate' => false,
            'customCssClass' => null,
            'headerBgColor' => null,
            'headerTextColor' => 'auto',
            'customTemplate' => null,
            'cacheLifetime' => 86400,
            'customData' => null,
            'default' => false,
            'templateKey' => null,
        ];
    }

    private function entityToArray(ArticleConfiguration $entity): array
    {
        return [
            'articleId' => $entity->getArticleId(),
            'templateKey' => $entity->getTemplateKey(),
            'default' => $entity->isDefault(),
            'layoutStyle' => $entity->getLayoutStyle(),
            'showToc' => $entity->isShowToc(),
            'showReadingTime' => $entity->isShowReadingTime(),
            'showAuthorBox' => $entity->isShowAuthorBox(),
            'showRelated' => $entity->isShowRelated(),
            'enableSidebar' => $entity->isEnableSidebar(),
            'sidebarPosition' => $entity->getSidebarPosition(),
            'enableComments' => $entity->isEnableComments(),
            'enableShareButtons' => $entity->isEnableShareButtons(),
            'enablePrint' => $entity->isEnablePrint(),
            'enableDownloadPdf' => $entity->isEnableDownloadPdf(),
            'isFeatured' => $entity->isFeatured(),
            'isSticky' => $entity->isSticky(),
            'hideFromLists' => $entity->isHideFromLists(),
            'hidePublishDate' => $entity->isHidePublishDate(),
            'customCssClass' => $entity->getCustomCssClass(),
            'headerBgColor' => $entity->getHeaderBgColor(),
            'headerTextColor' => $entity->getHeaderTextColor(),
            'customTemplate' => $entity->getCustomTemplate(),
            'cacheLifetime' => $entity->getCacheLifetime(),
            'customData' => $entity->getCustomData(),
        ];
    }
}