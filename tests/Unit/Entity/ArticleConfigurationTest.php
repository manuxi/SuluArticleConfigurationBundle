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
        $this->assertTrue($configuration->isEnableShareButtons());
        $this->assertTrue($configuration->isEnablePrint());
        $this->assertFalse($configuration->isEnableDownloadPdf());
        $this->assertTrue($configuration->isPdfShowCaptions());
        $this->assertTrue($configuration->isPdfShowAuthor());
        $this->assertTrue($configuration->isPdfShowModified());
        $this->assertTrue($configuration->isPdfShowOnlineLink());
        $this->assertSame('none', $configuration->getPdfCompanyData());
        $this->assertFalse($configuration->isHidePublishDate());
        $this->assertNull($configuration->getCustomCssClass());
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

        $configuration->setEnableShareButtons(false);
        $this->assertFalse($configuration->isEnableShareButtons());

        $configuration->setEnablePrint(false);
        $this->assertFalse($configuration->isEnablePrint());

        $configuration->setEnableDownloadPdf(true);
        $this->assertTrue($configuration->isEnableDownloadPdf());

        $configuration->setPdfShowCaptions(false);
        $this->assertFalse($configuration->isPdfShowCaptions());

        $configuration->setPdfShowAuthor(false);
        $this->assertFalse($configuration->isPdfShowAuthor());

        $configuration->setPdfShowModified(false);
        $this->assertFalse($configuration->isPdfShowModified());

        $configuration->setPdfShowOnlineLink(false);
        $this->assertFalse($configuration->isPdfShowOnlineLink());

        $configuration->setPdfCompanyData('footer');
        $this->assertSame('footer', $configuration->getPdfCompanyData());

        $configuration->setHidePublishDate(true);
        $this->assertTrue($configuration->isHidePublishDate());

        $configuration->setCustomCssClass('my-class');
        $this->assertSame('my-class', $configuration->getCustomCssClass());
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