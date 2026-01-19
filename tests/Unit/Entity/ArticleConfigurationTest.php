<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Entity;

use Manuxi\SuluArticleConfigurationBundle\Entity\ArticleConfiguration;
use PHPUnit\Framework\TestCase;

class ArticleConfigurationTest extends TestCase
{
    public function testDefaults(): void
    {
        $entity = new ArticleConfiguration();

        $this->assertNull($entity->getId());
        $this->assertNull($entity->getArticleId());
        $this->assertEquals('default', $entity->getLayoutStyle());
        $this->assertTrue($entity->isEnableSidebar());
        $this->assertEquals('right', $entity->getSidebarPosition());
        $this->assertTrue($entity->isShowToc());
        $this->assertTrue($entity->isShowReadingTime());
        $this->assertTrue($entity->isShowAuthorBox());
        $this->assertTrue($entity->isShowRelated());
        $this->assertFalse($entity->isEnableComments());
        $this->assertTrue($entity->isEnableShareButtons());
        $this->assertTrue($entity->isEnablePrint());
        $this->assertFalse($entity->isEnableDownloadPdf());
        $this->assertFalse($entity->isFeatured());
        $this->assertFalse($entity->isSticky());
        $this->assertFalse($entity->isHideFromLists());
        $this->assertFalse($entity->isHidePublishDate());
        $this->assertEquals('auto', $entity->getHeaderTextColor());
    }

    public function testSettersAndGetters(): void
    {
        $entity = new ArticleConfiguration();

        $entity->setArticleId('123-456');
        $this->assertEquals('123-456', $entity->getArticleId());

        $entity->setLayoutStyle('wide');
        $this->assertEquals('wide', $entity->getLayoutStyle());

        $entity->setEnableSidebar(false);
        $this->assertFalse($entity->isEnableSidebar());

        $entity->setSidebarPosition('left');
        $this->assertEquals('left', $entity->getSidebarPosition());

        $entity->setCustomCssClass('my-class');
        $this->assertEquals('my-class', $entity->getCustomCssClass());

        $entity->setHeaderBgColor('#ffffff');
        $this->assertEquals('#ffffff', $entity->getHeaderBgColor());

        $entity->setCustomTemplate('test.html.twig');
        $this->assertEquals('test.html.twig', $entity->getCustomTemplate());

        $entity->setCacheLifetime(3600);
        $this->assertEquals(3600, $entity->getCacheLifetime());

        $data = json_encode(['foo' => 'bar']);
        $entity->setCustomData($data);
        $this->assertEquals($data, $entity->getCustomData());
    }
}
