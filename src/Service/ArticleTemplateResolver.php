<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Service;

use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

/**
 * Resolves an article's own template key from its frontend URL -- for use where an article is only known by its
 * URL (e.g. an item in a smart_content/selection list of a different content type), not by its own controller
 * context. Sulu's collection/smart_content "properties" mapping (see the field type's "properties" param) cannot
 * reach the template key: it lives in the resource's "view" data, and
 * ContentViewDataNormalizer::recursivelyMapProperties() explicitly skips that branch ("views cannot be mapped via
 * properties"). "url" is reachable (it is regular content data), so this goes url -> route -> uuid -> article ->
 * template key instead.
 */
class ArticleTemplateResolver
{
    public function __construct(
        private RouteRepositoryInterface $routeRepository,
        private ArticleRepositoryInterface $articleRepository,
        private ContentManagerInterface $contentManager,
    ) {
    }

    public function resolveTemplateKey(string $url, string $locale): ?string
    {
        try {
            $route = $this->routeRepository->findOneBy(['slug' => $url, 'locale' => $locale, 'resourceKey' => 'articles']);
            if (null === $route) {
                return null;
            }

            $article = $this->articleRepository->findOneBy(['uuid' => $route->getResourceId(), 'locale' => $locale, 'stage' => DimensionContentInterface::STAGE_LIVE]);
            if (null === $article) {
                return null;
            }

            $dimensionContent = $this->contentManager->resolve($article, ['locale' => $locale, 'stage' => DimensionContentInterface::STAGE_LIVE]);

            return $dimensionContent instanceof ArticleDimensionContentInterface ? $dimensionContent->getTemplateKey() : null;
        } catch (\Throwable) {
            // a lookup like this must never break the page it is used on
            return null;
        }
    }
}
