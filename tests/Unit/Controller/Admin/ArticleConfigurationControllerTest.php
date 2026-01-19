<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Manuxi\SuluArticleConfigurationBundle\Controller\Admin\ArticleConfigurationController;
use Manuxi\SuluArticleConfigurationBundle\Entity\ArticleConfiguration;
use Manuxi\SuluArticleConfigurationBundle\Repository\ArticleConfigurationRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleConfigurationControllerTest extends TestCase
{
    private $repository;
    private $entityManager;
    private $viewHandler;
    private $controller;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ArticleConfigurationRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->viewHandler = $this->createMock(ViewHandlerInterface::class);

        $this->controller = new ArticleConfigurationController(
            $this->repository,
            $this->entityManager,
            $this->viewHandler
        );
    }

    public function testGetActionFound(): void
    {
        $articleId = '123-456';
        $configuration = new ArticleConfiguration();
        $configuration->setArticleId($articleId);
        $configuration->setLayoutStyle('wide');
        $configuration->setEnableSidebar(false);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['articleId' => $articleId])
            ->willReturn($configuration);

        $this->viewHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (View $view) use ($articleId) {
                $data = $view->getData();
                return \is_array($data)
                    && $data['id'] === $articleId
                    && $data['articleId'] === $articleId
                    && $data['layoutStyle'] === 'wide'
                    && $data['enableSidebar'] === false;
            }))
            ->willReturn(new Response());

        $this->controller->getAction($articleId);
    }

    public function testGetActionNotFound(): void
    {
        $articleId = '123-456';

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['articleId' => $articleId])
            ->willReturn(null);

        $this->viewHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (View $view) use ($articleId) {
                $data = $view->getData();
                return \is_array($data)
                    && $data['id'] === $articleId
                    && $data['articleId'] === $articleId
                    && $data['layoutStyle'] === 'default'
                    && $data['enableSidebar'] === true;
            }))
            ->willReturn(new Response());

        $this->controller->getAction($articleId);
    }

    public function testPutActionExisting(): void
    {
        $articleId = '123-456';
        $configuration = new ArticleConfiguration();
        $configuration->setArticleId($articleId);

        $request = new Request([], [], [], [], [], [], json_encode([
            'layoutStyle' => 'wide',
            'enableSidebar' => false,
            'sidebarPosition' => 'left',
        ]));

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['articleId' => $articleId])
            ->willReturn($configuration);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->viewHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (View $view) use ($articleId) {
                $data = $view->getData();
                return \is_array($data)
                    && $data['id'] === $articleId
                    && $data['layoutStyle'] === 'wide'
                    && $data['enableSidebar'] === false
                    && $data['sidebarPosition'] === 'left';
            }))
            ->willReturn(new Response());

        $this->controller->putAction($articleId, $request);
    }

    public function testPutActionNew(): void
    {
        $articleId = 'new-123';

        $request = new Request([], [], [], [], [], [], json_encode([
            'layoutStyle' => 'fullwidth',
            'isFeatured' => true,
        ]));

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['articleId' => $articleId])
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(ArticleConfiguration::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->viewHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (View $view) use ($articleId) {
                $data = $view->getData();
                return \is_array($data)
                    && $data['id'] === $articleId
                    && $data['layoutStyle'] === 'fullwidth'
                    && $data['isFeatured'] === true;
            }))
            ->willReturn(new Response());

        $this->controller->putAction($articleId, $request);
    }

    public function testGetActionReturnsAllFields(): void
    {
        $articleId = 'test-uuid';

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->viewHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (View $view) {
                $data = $view->getData();
                $expectedFields = [
                    'id', 'articleId', 'layoutStyle', 'showToc', 'showReadingTime',
                    'showAuthorBox', 'showRelated', 'enableSidebar', 'sidebarPosition',
                    'enableComments', 'enableShareButtons', 'enablePrint', 'enableDownloadPdf',
                    'isFeatured', 'isSticky', 'hideFromLists', 'hidePublishDate',
                    'customCssClass', 'headerBgColor', 'headerTextColor',
                    'customTemplate', 'cacheLifetime', 'customData',
                ];

                foreach ($expectedFields as $field) {
                    if (!\array_key_exists($field, $data)) {
                        return false;
                    }
                }

                return true;
            }))
            ->willReturn(new Response());

        $this->controller->getAction($articleId);
    }
}