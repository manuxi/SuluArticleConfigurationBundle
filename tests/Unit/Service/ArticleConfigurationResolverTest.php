<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Service;

use Manuxi\SuluArticleConfigurationBundle\Entity\ArticleConfiguration;
use Manuxi\SuluArticleConfigurationBundle\Repository\ArticleConfigurationRepository;
use Manuxi\SuluArticleConfigurationBundle\Service\ArticleConfigurationResolver;
use PHPUnit\Framework\TestCase;

class ArticleConfigurationResolverTest extends TestCase
{
    private ArticleConfigurationRepository $repository;
    private ArticleConfigurationResolver $resolver;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ArticleConfigurationRepository::class);
        $this->resolver = new ArticleConfigurationResolver($this->repository);
    }

    public function testResolveWithArticleConfig(): void
    {
        $articleId = 'article-123';
        $templateKey = 'article_blog';

        $config = new ArticleConfiguration();
        $config->setArticleId($articleId);
        $config->setTemplateKey($templateKey);
        $config->setLayoutStyle('wide');

        $this->repository->expects($this->once())
            ->method('findByArticleId')
            ->with($articleId)
            ->willReturn($config);

        $this->repository->expects($this->never())
            ->method('findDefaultForTemplate');

        $result = $this->resolver->resolve($articleId, $templateKey);

        $this->assertEquals('article', $result['configSource']);
        $this->assertEquals('wide', $result['layoutStyle']);
        $this->assertEquals($articleId, $result['articleId']);
    }

    public function testResolveWithTemplateDefault(): void
    {
        $articleId = 'article-123';
        $templateKey = 'article_blog';
        $defaultArticleId = 'default-article';

        $defaultConfig = new ArticleConfiguration();
        $defaultConfig->setArticleId($defaultArticleId);
        $defaultConfig->setTemplateKey($templateKey);
        $defaultConfig->setDefault(true);
        $defaultConfig->setLayoutStyle('narrow');

        $this->repository->expects($this->once())
            ->method('findByArticleId')
            ->with($articleId)
            ->willReturn(null);

        $this->repository->expects($this->once())
            ->method('findDefaultForTemplate')
            ->with($templateKey)
            ->willReturn($defaultConfig);

        $result = $this->resolver->resolve($articleId, $templateKey);

        $this->assertEquals('template_default', $result['configSource']);
        $this->assertEquals('narrow', $result['layoutStyle']);
        $this->assertEquals($defaultArticleId, $result['templateDefaultArticleId']);
    }

    public function testResolveWithHardcodedDefaults(): void
    {
        $articleId = 'article-123';
        $templateKey = 'article_blog';

        $this->repository->expects($this->once())
            ->method('findByArticleId')
            ->with($articleId)
            ->willReturn(null);

        $this->repository->expects($this->once())
            ->method('findDefaultForTemplate')
            ->with($templateKey)
            ->willReturn(null);

        $result = $this->resolver->resolve($articleId, $templateKey);

        $this->assertEquals('hardcoded', $result['configSource']);
        $this->assertEquals('default', $result['layoutStyle']);
        $this->assertEquals($templateKey, $result['templateKey']);
    }

    public function testResolveWithoutTemplateKey(): void
    {
        $articleId = 'article-123';

        $this->repository->expects($this->once())
            ->method('findByArticleId')
            ->with($articleId)
            ->willReturn(null);

        $this->repository->expects($this->never())
            ->method('findDefaultForTemplate');

        $result = $this->resolver->resolve($articleId, null);

        $this->assertEquals('hardcoded', $result['configSource']);
    }
}