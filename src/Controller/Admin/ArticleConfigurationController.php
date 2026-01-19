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
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/api')]
class ArticleConfigurationController extends AbstractRestController
{
    public function __construct(
        private ArticleConfigurationRepository $repository,
        private EntityManagerInterface $entityManager,
        ViewHandlerInterface $viewHandler
    ) {
        parent::__construct($viewHandler);
    }
    #[Route('/article-configurations/{id}', name: 'app.get_article_configurations', methods: ['GET'])]
    public function getAction(string $id): Response
    {
        $configuration = $this->repository->findOneBy(['articleId' => $id]);
        if (!$configuration) {
            // Return default empty structure if not found
            return $this->handleView($this->view([
                'id' => null, // Explicitly null ID for new
                'articleId' => $id,
                'layoutStyle' => 'default',
                'enableSidebar' => true,
                'sidebarPosition' => 'right',
                'showToc' => true,
                'showReadingTime' => true,
                'showAuthorBox' => true,
                'showRelated' => true,
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
            ]));
        }
        return $this->handleView($this->view($configuration));
    }
    #[Route('/article-configurations/{id}', name: 'app.put_article_configurations', methods: ['PUT'])]
    public function putAction(string $id, Request $request): Response
    {
        $configuration = $this->repository->findOneBy(['articleId' => $id]);
        if (!$configuration) {
            $configuration = new ArticleConfiguration();
            $configuration->setArticleId($id);
            $this->entityManager->persist($configuration);
        }
        $data = $request->toArray();
        $configuration->setLayoutStyle($data['layout_style'] ?? 'default');
        $configuration->setEnableSidebar($data['enable_sidebar'] ?? true);
        $configuration->setSidebarPosition($data['sidebar_position'] ?? 'right');
        $configuration->setShowToc($data['show_toc'] ?? true);
        $configuration->setShowReadingTime($data['show_reading_time'] ?? true);
        $configuration->setShowAuthorBox($data['show_author_box'] ?? true);
        $configuration->setShowRelated($data['show_related'] ?? true);
        $configuration->setEnableComments($data['enable_comments'] ?? false);
        $configuration->setEnableShareButtons($data['enable_share_buttons'] ?? true);
        $configuration->setEnablePrint($data['enable_print'] ?? true);
        $configuration->setEnableDownloadPdf($data['enable_download_pdf'] ?? false);
        $configuration->setIsFeatured($data['is_featured'] ?? false);
        $configuration->setIsSticky($data['is_sticky'] ?? false);
        $configuration->setHideFromLists($data['hide_from_lists'] ?? false);
        $configuration->setHidePublishDate($data['hide_publish_date'] ?? false);
        $configuration->setCustomCssClass($data['custom_css_class'] ?? null);
        $configuration->setHeaderBgColor($data['header_bg_color'] ?? null);
        $configuration->setHeaderTextColor($data['header_text_color'] ?? 'auto');
        $configuration->setCustomTemplate($data['custom_template'] ?? null);
        $configuration->setCacheLifetime(isset($data['cache_lifetime']) ? (int) $data['cache_lifetime'] : 86400);
        $configuration->setCustomData($data['custom_data'] ?? null);
        $this->entityManager->flush();
        return $this->handleView($this->view($configuration));
    }
}