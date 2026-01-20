<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Manuxi\SuluArticleConfigurationBundle\Entity\ArticleConfiguration;
use Manuxi\SuluArticleConfigurationBundle\Repository\ArticleConfigurationRepository;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Component\Rest\AbstractRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/api')]
class ArticleConfigurationController extends AbstractRestController
{
    public function __construct(
        private ArticleConfigurationRepository $repository,
        private EntityManagerInterface $entityManager,
        private ArticleRepositoryInterface $articleRepository,
        private ContentAggregatorInterface $contentAggregator,
        ViewHandlerInterface $viewHandler
    ) {
        parent::__construct($viewHandler);
    }

    #[Route(
        path: '/article-configurations/{id}',
        name: 'app.get_article_configurations',
        methods: ['GET'],
        defaults: ['_format' => 'json']
    )]
    public function getAction(string $id, Request $request): Response
    {
        $configuration = $this->repository->findByArticleId($id);

        if (!$configuration) {
            return $this->handleView($this->view($this->getDefaultData($id)));
        }

        return $this->handleView($this->view($this->serializeConfiguration($configuration)));
    }

    #[Route(
        path: '/article-configurations/{id}',
        name: 'app.put_article_configurations',
        methods: ['PUT'],
        defaults: ['_format' => 'json']
    )]
    public function putAction(string $id, Request $request): Response
    {
        $locale = $request->query->get('locale', 'en');
        $configuration = $this->repository->findByArticleId($id);

        if (!$configuration) {
            $configuration = new ArticleConfiguration();
            $configuration->setArticleId($id);
            $this->entityManager->persist($configuration);
        }

        $data = $request->toArray();

        $templateKey = $this->getTemplateKeyFromArticle($id, $locale);
        $configuration->setTemplateKey($templateKey);

        $default = $data['default'] ?? false;
        if ($default && $templateKey) {
            $this->repository->clearDefaultsForTemplate($templateKey, $id);
        }
        $configuration->setDefault($default);

        $configuration->setLayoutStyle($data['layoutStyle'] ?? 'default');
        $configuration->setShowToc($data['showToc'] ?? true);
        $configuration->setShowReadingTime($data['showReadingTime'] ?? true);
        $configuration->setShowAuthorBox($data['showAuthorBox'] ?? true);
        $configuration->setShowRelated($data['showRelated'] ?? true);

        $configuration->setEnableSidebar($data['enableSidebar'] ?? true);
        $configuration->setSidebarPosition($data['sidebarPosition'] ?? 'right');

        $configuration->setEnableComments($data['enableComments'] ?? false);
        $configuration->setEnableShareButtons($data['enableShareButtons'] ?? true);
        $configuration->setEnablePrint($data['enablePrint'] ?? true);
        $configuration->setEnableDownloadPdf($data['enableDownloadPdf'] ?? false);

        $configuration->setIsFeatured($data['isFeatured'] ?? false);
        $configuration->setIsSticky($data['isSticky'] ?? false);
        $configuration->setHideFromLists($data['hideFromLists'] ?? false);
        $configuration->setHidePublishDate($data['hidePublishDate'] ?? false);

        $configuration->setCustomCssClass($data['customCssClass'] ?? null);
        $configuration->setHeaderBgColor($data['headerBgColor'] ?? null);
        $configuration->setHeaderTextColor($data['headerTextColor'] ?? 'auto');

        $configuration->setCustomTemplate($data['customTemplate'] ?? null);
        $configuration->setCacheLifetime(isset($data['cacheLifetime']) ? (int) $data['cacheLifetime'] : 86400);
        $configuration->setCustomData($data['customData'] ?? null);

        $this->entityManager->flush();

        return $this->handleView($this->view($this->serializeConfiguration($configuration)));
    }

    private function getTemplateKeyFromArticle(string $articleId, string $locale): ?string
    {
        try {
            $article = $this->articleRepository->findOneBy(
                [
                    'uuid' => $articleId,
                    'locale' => $locale,
                    'stage' => DimensionContentInterface::STAGE_DRAFT,
                ],
                [
                    ArticleRepositoryInterface::SELECT_ARTICLE_CONTENT => [
                        DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true,
                    ],
                ]
            );

            if (!$article) {
                return null;
            }

            $dimensionContent = $this->contentAggregator->aggregate($article, [
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_DRAFT,
            ]);

            return $dimensionContent->getTemplateKey();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getDefaultData(string $id): array
    {
        return [
            'id' => $id,
            'articleId' => $id,
            'templateKey' => null,
            'default' => false,
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
        ];
    }

    private function serializeConfiguration(ArticleConfiguration $configuration): array
    {
        return [
            'id' => $configuration->getArticleId(),
            'articleId' => $configuration->getArticleId(),
            'templateKey' => $configuration->getTemplateKey(),
            'default' => $configuration->isDefault(),
            'layoutStyle' => $configuration->getLayoutStyle(),
            'showToc' => $configuration->isShowToc(),
            'showReadingTime' => $configuration->isShowReadingTime(),
            'showAuthorBox' => $configuration->isShowAuthorBox(),
            'showRelated' => $configuration->isShowRelated(),
            'enableSidebar' => $configuration->isEnableSidebar(),
            'sidebarPosition' => $configuration->getSidebarPosition(),
            'enableComments' => $configuration->isEnableComments(),
            'enableShareButtons' => $configuration->isEnableShareButtons(),
            'enablePrint' => $configuration->isEnablePrint(),
            'enableDownloadPdf' => $configuration->isEnableDownloadPdf(),
            'isFeatured' => $configuration->isFeatured(),
            'isSticky' => $configuration->isSticky(),
            'hideFromLists' => $configuration->isHideFromLists(),
            'hidePublishDate' => $configuration->isHidePublishDate(),
            'customCssClass' => $configuration->getCustomCssClass(),
            'headerBgColor' => $configuration->getHeaderBgColor(),
            'headerTextColor' => $configuration->getHeaderTextColor(),
            'customTemplate' => $configuration->getCustomTemplate(),
            'cacheLifetime' => $configuration->getCacheLifetime(),
            'customData' => $configuration->getCustomData(),
        ];
    }
}