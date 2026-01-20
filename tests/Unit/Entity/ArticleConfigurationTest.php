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

        $this->assertSame('test-id', $configuration->getArticleId());
        $this->assertNull($configuration->getTemplateKey());
        $this->assertFalse($configuration->isDefault());
        $this->assertSame('default', $configuration->getLayoutStyle());
        $this->assertTrue($configuration->isEnableSidebar());
        $this->assertSame('right', $configuration->getSidebarPosition());
        $this->assertTrue($configuration->isShowToc());
        $this->assertTrue($configuration->isShowReadingTime());
        $this->assertTrue($configuration->isShowAuthorBox());
        $this->assertTrue($configuration->isShowRelated());
        $this->assertFalse($configuration->isEnableComments());
        $this->assertTrue($configuration->isEnableShareButtons());
        $this->assertTrue($configuration->isEnablePrint());
        $this->assertFalse($configuration->isEnableDownloadPdf());
        $this->assertFalse($configuration->isFeatured());
        $this->assertFalse($configuration->isSticky());
        $this->assertFalse($configuration->isHideFromLists());
        $this->assertFalse($configuration->isHidePublishDate());
        $this->assertNull($configuration->getCustomCssClass());
        $this->assertNull($configuration->getHeaderBgColor());
        $this->assertSame('auto', $configuration->getHeaderTextColor());
        $this->assertNull($configuration->getCustomTemplate());
        $this->assertSame(86400, $configuration->getCacheLifetime());
        $this->assertNull($configuration->getCustomData());
    }

    public function testSettersAndGetters(): void
    {
        $configuration = new ArticleConfiguration();

        $configuration->setArticleId('article-123');
        $this->assertSame('article-123', $configuration->getArticleId());

        $configuration->setTemplateKey('article_blog');
        $this->assertSame('article_blog', $configuration->getTemplateKey());

        $configuration->setDefault(true);
        $this->assertTrue($configuration->isDefault());

        $configuration->setLayoutStyle('wide');
        $this->assertSame('wide', $configuration->getLayoutStyle());

        $configuration->setEnableSidebar(false);
        $this->assertFalse($configuration->isEnableSidebar());

        $configuration->setSidebarPosition('left');
        $this->assertSame('left', $configuration->getSidebarPosition());

        $configuration->setShowToc(false);
        $this->assertFalse($configuration->isShowToc());

        $configuration->setShowReadingTime(false);
        $this->assertFalse($configuration->isShowReadingTime());

        $configuration->setShowAuthorBox(false);
        $this->assertFalse($configuration->isShowAuthorBox());

        $configuration->setShowRelated(false);
        $this->assertFalse($configuration->isShowRelated());

        $configuration->setEnableComments(true);
        $this->assertTrue($configuration->isEnableComments());

        $configuration->setEnableShareButtons(false);
        $this->assertFalse($configuration->isEnableShareButtons());

        $configuration->setEnablePrint(false);
        $this->assertFalse($configuration->isEnablePrint());

        $configuration->setEnableDownloadPdf(true);
        $this->assertTrue($configuration->isEnableDownloadPdf());

        $configuration->setIsFeatured(true);
        $this->assertTrue($configuration->isFeatured());

        $configuration->setIsSticky(true);
        $this->assertTrue($configuration->isSticky());

        $configuration->setHideFromLists(true);
        $this->assertTrue($configuration->isHideFromLists());

        $configuration->setHidePublishDate(true);
        $this->assertTrue($configuration->isHidePublishDate());

        $configuration->setCustomCssClass('my-class');
        $this->assertSame('my-class', $configuration->getCustomCssClass());

        $configuration->setHeaderBgColor('#ff0000');
        $this->assertSame('#ff0000', $configuration->getHeaderBgColor());

        $configuration->setHeaderTextColor('light');
        $this->assertSame('light', $configuration->getHeaderTextColor());

        $configuration->setCustomTemplate('articles/special.html.twig');
        $this->assertSame('articles/special.html.twig', $configuration->getCustomTemplate());

        $configuration->setCacheLifetime(3600);
        $this->assertSame(3600, $configuration->getCacheLifetime());

        $configuration->setCustomData('{"key": "value"}');
        $this->assertSame('{"key": "value"}', $configuration->getCustomData());
    }

    public function testFluentInterface(): void
    {
        $configuration = new ArticleConfiguration();

        $result = $configuration
            ->setArticleId('test')
            ->setTemplateKey('blog')
            ->setDefault(true)
            ->setLayoutStyle('wide')
            ->setEnableSidebar(true)
            ->setSidebarPosition('left');

        $this->assertSame($configuration, $result);
    }
}