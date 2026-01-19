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

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['articleId' => $articleId])
            ->willReturn($configuration);

        $this->viewHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (View $view) use ($configuration) {
                return $view->getData() === $configuration;
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
                return is_array($data) && $data['articleId'] === $articleId && $data['layoutStyle'] === 'default';
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
            'layout_style' => 'wide',
            'enable_sidebar' => false,
        ]));

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['articleId' => $articleId])
            ->willReturn($configuration);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->viewHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (View $view) {
                $config = $view->getData();
                return $config instanceof ArticleConfiguration
                    && $config->getLayoutStyle() === 'wide'
                    && $config->isEnableSidebar() === false;
            }))
            ->willReturn(new Response());

        $this->controller->putAction($articleId, $request);
    }

    public function testPutActionNew(): void
    {
        $articleId = 'new-123';

        $request = new Request([], [], [], [], [], [], json_encode([
            'layout_style' => 'fullwidth',
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
            ->willReturn(new Response());

        $this->controller->putAction($articleId, $request);
    }
}
