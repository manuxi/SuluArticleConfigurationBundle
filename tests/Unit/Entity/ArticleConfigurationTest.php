<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Entity;

use Manuxi\SuluArticleConfigurationBundle\Entity\ArticleConfiguration;
use PHPUnit\Framework\TestCase;

class ArticleConfigurationTest extends TestCase
{
    public function testDefaults(): void
    {
        $configuration = new ArticleConfiguration();
        $configuration->setArticleId('test-id');

        $this->assertNull($configuration->getId());
        $this->assertSame('test-id', $configuration->getArticleId());
        $this->assertNull($configuration->getTemplateKey());
        $this->assertFalse($configuration->isDefault());
        $this->assertSame([], $configuration->getData());
    }

    public function testSettersAndGetters(): void
    {
        $configuration = new ArticleConfiguration();

        $this->assertSame($configuration, $configuration->setArticleId('article-123'));
        $this->assertSame($configuration, $configuration->setTemplateKey('article_blog'));
        $this->assertSame($configuration, $configuration->setDefault(true));
        $this->assertSame($configuration, $configuration->setData(['layoutStyle' => 'wide', 'showToc' => false]));

        $this->assertSame('article-123', $configuration->getArticleId());
        $this->assertSame('article_blog', $configuration->getTemplateKey());
        $this->assertTrue($configuration->isDefault());
        $this->assertSame(['layoutStyle' => 'wide', 'showToc' => false], $configuration->getData());
    }

    public function testTemplateKeyCanBeReset(): void
    {
        $configuration = new ArticleConfiguration();
        $configuration->setTemplateKey('article_blog');
        $configuration->setTemplateKey(null);

        $this->assertNull($configuration->getTemplateKey());
    }
}
