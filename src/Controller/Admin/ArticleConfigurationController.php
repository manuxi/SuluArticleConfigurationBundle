<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Manuxi\SuluArticleConfigurationBundle\Entity\ArticleConfiguration;
use Manuxi\SuluArticleConfigurationBundle\Repository\ArticleConfigurationRepository;
use Sulu\Component\Rest\AbstractRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ArticleConfigurationController extends AbstractRestController
{
    public function __construct(
        private ArticleConfigurationRepository $repository,
        private EntityManagerInterface $entityManager,
        ViewHandlerInterface $viewHandler
    ) {
        parent::__construct($viewHandler);
    }

    #[Route(
        path: '/admin/api/article-configurations/{id}',
        name: 'app.get_article_configurations',
        methods: ['GET'],
        defaults: ['_format' => 'json']
    )]
    public function getAction(string $id): Response
    {
        $configuration = $this->repository->findOneBy(['articleId' => $id]);

        if (!$configuration) {
            return $this->handleView($this->view($this->getDefaultData($id)));
        }

        return $this->handleView($this->view($this->serializeConfiguration($configuration)));
    }

    #[Route(
        path: '/admin/api/article-configurations/{id}',
        name: 'app.put_article_configurations',
        methods: ['PUT'],
        defaults: ['_format' => 'json']
    )]
    public function putAction(string $id, Request $request): Response
    {
        $configuration = $this->repository->findOneBy(['articleId' => $id]);

        if (!$configuration) {
            $configuration = new ArticleConfiguration();
            $configuration->setArticleId($id);
            $this->entityManager->persist($configuration);
        }

        $data = $request->toArray();

        // Display Options
        $configuration->setLayoutStyle($data['layoutStyle'] ?? 'default');
        $configuration->setShowToc($data['showToc'] ?? true);
        $configuration->setShowReadingTime($data['showReadingTime'] ?? true);
        $configuration->setShowAuthorBox($data['showAuthorBox'] ?? true);
        $configuration->setShowRelated($data['showRelated'] ?? true);

        // Sidebar Options
        $configuration->setEnableSidebar($data['enableSidebar'] ?? true);
        $configuration->setSidebarPosition($data['sidebarPosition'] ?? 'right');

        // Features
        $configuration->setEnableComments($data['enableComments'] ?? false);
        $configuration->setEnableShareButtons($data['enableShareButtons'] ?? true);
        $configuration->setEnablePrint($data['enablePrint'] ?? true);
        $configuration->setEnableDownloadPdf($data['enableDownloadPdf'] ?? false);

        // Publication Settings
        $configuration->setIsFeatured($data['isFeatured'] ?? false);
        $configuration->setIsSticky($data['isSticky'] ?? false);
        $configuration->setHideFromLists($data['hideFromLists'] ?? false);
        $configuration->setHidePublishDate($data['hidePublishDate'] ?? false);

        // Styling
        $configuration->setCustomCssClass($data['customCssClass'] ?? null);
        $configuration->setHeaderBgColor($data['headerBgColor'] ?? null);
        $configuration->setHeaderTextColor($data['headerTextColor'] ?? 'auto');

        // Advanced
        $configuration->setCustomTemplate($data['customTemplate'] ?? null);
        $configuration->setCacheLifetime(isset($data['cacheLifetime']) ? (int) $data['cacheLifetime'] : 86400);
        $configuration->setCustomData($data['customData'] ?? null);

        $this->entityManager->flush();

        return $this->handleView($this->view($this->serializeConfiguration($configuration)));
    }

    private function getDefaultData(string $id): array
    {
        return [
            'id' => $id,
            'articleId' => $id,
            // Display Options
            'layoutStyle' => 'default',
            'showToc' => true,
            'showReadingTime' => true,
            'showAuthorBox' => true,
            'showRelated' => true,
            // Sidebar Options
            'enableSidebar' => true,
            'sidebarPosition' => 'right',
            // Features
            'enableComments' => false,
            'enableShareButtons' => true,
            'enablePrint' => true,
            'enableDownloadPdf' => false,
            // Publication Settings
            'isFeatured' => false,
            'isSticky' => false,
            'hideFromLists' => false,
            'hidePublishDate' => false,
            // Styling
            'customCssClass' => null,
            'headerBgColor' => null,
            'headerTextColor' => 'auto',
            // Advanced
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
            // Display Options
            'layoutStyle' => $configuration->getLayoutStyle(),
            'showToc' => $configuration->isShowToc(),
            'showReadingTime' => $configuration->isShowReadingTime(),
            'showAuthorBox' => $configuration->isShowAuthorBox(),
            'showRelated' => $configuration->isShowRelated(),
            // Sidebar Options
            'enableSidebar' => $configuration->isEnableSidebar(),
            'sidebarPosition' => $configuration->getSidebarPosition(),
            // Features
            'enableComments' => $configuration->isEnableComments(),
            'enableShareButtons' => $configuration->isEnableShareButtons(),
            'enablePrint' => $configuration->isEnablePrint(),
            'enableDownloadPdf' => $configuration->isEnableDownloadPdf(),
            // Publication Settings
            'isFeatured' => $configuration->isFeatured(),
            'isSticky' => $configuration->isSticky(),
            'hideFromLists' => $configuration->isHideFromLists(),
            'hidePublishDate' => $configuration->isHidePublishDate(),
            // Styling
            'customCssClass' => $configuration->getCustomCssClass(),
            'headerBgColor' => $configuration->getHeaderBgColor(),
            'headerTextColor' => $configuration->getHeaderTextColor(),
            // Advanced
            'customTemplate' => $configuration->getCustomTemplate(),
            'cacheLifetime' => $configuration->getCacheLifetime(),
            'customData' => $configuration->getCustomData(),
        ];
    }
}