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
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleConfigurationControllerTest extends TestCase
{
    private ArticleConfigurationRepository $repository;
    private EntityManagerInterface $entityManager;
    private ArticleRepositoryInterface $articleRepository;
    private ContentAggregatorInterface $contentAggregator;
    private ViewHandlerInterface $viewHandler;
    private ArticleConfigurationController $controller;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ArticleConfigurationRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->articleRepository = $this->createMock(ArticleRepositoryInterface::class);
        $this->contentAggregator = $this->createMock(ContentAggregatorInterface::class);
        $this->viewHandler = $this->createMock(ViewHandlerInterface::class);

        $this->controller = new ArticleConfigurationController(
            $this->repository,
            $this->entityManager,
            $this->articleRepository,
            $this->contentAggregator,
            $this->viewHandler
        );
    }

    private function mockArticleWithTemplateKey(?string $templateKey): void
    {
        if ($templateKey) {
            $article = $this->createMock(ArticleInterface::class);
            $this->articleRepository->method('findOneBy')->willReturn($article);

            $dimensionContent = $this->createMock(ArticleDimensionContentInterface::class);
            $dimensionContent->method('getTemplateKey')->willReturn($templateKey);
            $this->contentAggregator->method('aggregate')->willReturn($dimensionContent);
        } else {
            $this->articleRepository->method('findOneBy')->willReturn(null);
        }
    }

    public function testGetActionFound(): void
    {
        $articleId = '123-456';
        $templateKey = 'article_blog';

        $this->mockArticleWithTemplateKey($templateKey);

        $configuration = new ArticleConfiguration();
        $configuration->setArticleId($articleId);
        $configuration->setTemplateKey($templateKey);
        $configuration->setDefault(true);
        $configuration->setLayoutStyle('wide');

        $this->repository->expects($this->once())
            ->method('findByArticleId')
            ->with($articleId)
            ->willReturn($configuration);

        $this->viewHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (View $view) use ($articleId, $templateKey) {
                $data = $view->getData();
                return \is_array($data)
                    && $data['id'] === $articleId
                    && $data['templateKey'] === $templateKey
                    && $data['default'] === true
                    && $data['layoutStyle'] === 'wide';
            }))
            ->willReturn(new Response());

        $request = new Request();
        $this->controller->getAction($articleId, $request);
    }

    public function testGetActionNotFound(): void
    {
        $articleId = '123-456';

        $this->mockArticleWithTemplateKey(null);

        $this->repository->expects($this->once())
            ->method('findByArticleId')
            ->with($articleId)
            ->willReturn(null);

        $this->viewHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (View $view) use ($articleId) {
                $data = $view->getData();
                return \is_array($data)
                    && $data['id'] === $articleId
                    && $data['templateKey'] === null
                    && $data['default'] === false;
            }))
            ->willReturn(new Response());

        $request = new Request();
        $this->controller->getAction($articleId, $request);
    }

    public function testPutActionNew(): void
    {
        $articleId = '123-456';
        $templateKey = 'article_blog';

        $this->mockArticleWithTemplateKey($templateKey);

        $this->repository->expects($this->once())
            ->method('findByArticleId')
            ->with($articleId)
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->viewHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (View $view) use ($templateKey) {
                $data = $view->getData();
                return \is_array($data) && $data['templateKey'] === $templateKey;
            }))
            ->willReturn(new Response());

        $request = new Request([], [], [], [], [], [], json_encode([
            'layoutStyle' => 'wide',
        ]));

        $this->controller->putAction($articleId, $request);
    }

    public function testPutActionExisting(): void
    {
        $articleId = '123-456';
        $templateKey = 'article_blog';

        $this->mockArticleWithTemplateKey($templateKey);

        $configuration = new ArticleConfiguration();
        $configuration->setArticleId($articleId);

        $this->repository->expects($this->once())
            ->method('findByArticleId')
            ->with($articleId)
            ->willReturn($configuration);

        $this->entityManager->expects($this->never())
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->viewHandler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $request = new Request([], [], [], [], [], [], json_encode([
            'layoutStyle' => 'narrow',
        ]));

        $this->controller->putAction($articleId, $request);
    }

    public function testPutActionWithDefault(): void
    {
        $articleId = '123-456';
        $templateKey = 'article_blog';

        $this->mockArticleWithTemplateKey($templateKey);

        $configuration = new ArticleConfiguration();
        $configuration->setArticleId($articleId);

        $this->repository->expects($this->once())
            ->method('findByArticleId')
            ->with($articleId)
            ->willReturn($configuration);

        $this->repository->expects($this->once())
            ->method('clearDefaultsForTemplate')
            ->with($templateKey, $articleId);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->viewHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (View $view) use ($articleId, $templateKey) {
                $data = $view->getData();
                return \is_array($data)
                    && $data['id'] === $articleId
                    && $data['templateKey'] === $templateKey
                    && $data['default'] === true;
            }))
            ->willReturn(new Response());

        $request = new Request([], [], [], [], [], [], json_encode([
            'default' => true,
            'layoutStyle' => 'wide',
        ]));

        $this->controller->putAction($articleId, $request);
    }

    public function testPutActionIgnoresTemplateKeyFromRequest(): void
    {
        $articleId = '123-456';
        $realTemplateKey = 'article_blog';
        $fakeTemplateKey = 'fake_injected_key';

        $this->mockArticleWithTemplateKey($realTemplateKey);

        $configuration = new ArticleConfiguration();
        $configuration->setArticleId($articleId);

        $this->repository->expects($this->once())
            ->method('findByArticleId')
            ->willReturn($configuration);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->viewHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (View $view) use ($realTemplateKey, $fakeTemplateKey) {
                $data = $view->getData();
                return \is_array($data)
                    && $data['templateKey'] === $realTemplateKey
                    && $data['templateKey'] !== $fakeTemplateKey;
            }))
            ->willReturn(new Response());

        $request = new Request([], [], [], [], [], [], json_encode([
            'templateKey' => $fakeTemplateKey,
            'layoutStyle' => 'wide',
        ]));

        $this->controller->putAction($articleId, $request);
    }

    public function testGetActionReturnsAllFields(): void
    {
        $articleId = 'test-uuid';

        $this->mockArticleWithTemplateKey(null);

        $this->repository->expects($this->once())
            ->method('findByArticleId')
            ->willReturn(null);

        $this->viewHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (View $view) {
                $data = $view->getData();
                $expectedFields = [
                    'id', 'articleId', 'templateKey', 'default',
                    'layoutStyle', 'showToc', 'showReadingTime',
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

        $request = new Request();
        $this->controller->getAction($articleId, $request);
    }
}