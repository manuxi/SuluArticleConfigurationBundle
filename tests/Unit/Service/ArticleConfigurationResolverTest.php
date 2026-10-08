<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Service;

use Manuxi\SuluArticleConfigurationBundle\Entity\ArticleConfiguration;
use Manuxi\SuluArticleConfigurationBundle\Repository\ArticleConfigurationRepository;
use Manuxi\SuluArticleConfigurationBundle\Tests\Support\TestSchemaFactory;
use Manuxi\SuluArticleConfigurationBundle\Service\ArticleConfigurationResolver;
use PHPUnit\Framework\TestCase;

class ArticleConfigurationResolverTest extends TestCase
{
    private ArticleConfigurationRepository $repository;
    private ArticleConfigurationResolver $resolver;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ArticleConfigurationRepository::class);
        $this->resolver = new ArticleConfigurationResolver($this->repository, TestSchemaFactory::createSchema());
    }

    public function testResolveWithArticleConfig(): void
    {
        $articleId = 'article-123';
        $templateKey = 'article_blog';

        $config = new ArticleConfiguration();
        $config->setArticleId($articleId);
        $config->setTemplateKey($templateKey);
        $config->setData(['layoutStyle' => 'wide']);

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
        $defaultConfig->setData(['layoutStyle' => 'narrow']);

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
        $this->assertEquals('fullwidth', $result['layoutStyle']);
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

    public function testResolveFillsMissingKeysWithSchemaDefaultsAndDropsRemovedFields(): void
    {
        $config = new ArticleConfiguration();
        $config->setArticleId('article-123');
        $config->setTemplateKey(TestSchemaFactory::BLOG_TEMPLATE);
        $config->setData(['layoutStyle' => 'wide', 'showToc' => true, 'obsoleteKey' => 'x']);

        $this->repository->method('findByArticleId')->willReturn($config);

        $result = $this->resolver->resolve('article-123', TestSchemaFactory::BLOG_TEMPLATE);

        $this->assertSame('wide', $result['layoutStyle']);
        $this->assertSame('video', $result['heroVariant']);
        $this->assertTrue($result['showRelated']);
        $this->assertArrayNotHasKey('showToc', $result);
        $this->assertArrayNotHasKey('obsoleteKey', $result);
    }

    public function testResolveHardcodedUsesSchemaOfTemplate(): void
    {
        $this->repository->method('findByArticleId')->willReturn(null);
        $this->repository->method('findDefaultForTemplate')->willReturn(null);

        $result = $this->resolver->resolve('article-123', TestSchemaFactory::BLOG_TEMPLATE);

        $this->assertArrayNotHasKey('showToc', $result);
        $this->assertSame('video', $result['heroVariant']);
        $this->assertFalse($result['default']);
    }

    public function testResolveWithoutTemplateKeyUsesBaseSchema(): void
    {
        $this->repository->method('findByArticleId')->willReturn(null);

        $result = $this->resolver->resolve('article-123');

        $this->assertTrue($result['showToc']);
        $this->assertArrayNotHasKey('heroVariant', $result);
        $this->assertNull($result['templateKey']);
    }

    public function testGetForArticleAndTemplateDefault(): void
    {
        $config = new ArticleConfiguration();
        $this->repository->method('findByArticleId')->with('a')->willReturn($config);
        $this->repository->method('findDefaultForTemplate')->with('t')->willReturn($config);

        $this->assertSame($config, $this->resolver->getForArticle('a'));
        $this->assertSame($config, $this->resolver->getTemplateDefault('t'));
    }
}
