<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\SymfonyFileLocator;
use Manuxi\SuluArticleConfigurationBundle\Entity\ArticleConfiguration;
use PHPUnit\Framework\TestCase;

class ArticleConfigurationMappingTest extends TestCase
{
    private function loadMetadata(): ClassMetadata
    {
        $locator = new SymfonyFileLocator(
            [\dirname(__DIR__, 3) . '/src/Resources/config/doctrine' => 'Manuxi\\SuluArticleConfigurationBundle\\Entity'],
            '.orm.xml'
        );
        $driver = new XmlDriver($locator, '.orm.xml');
        $metadata = new ClassMetadata(ArticleConfiguration::class);
        $driver->loadMetadataForClass(ArticleConfiguration::class, $metadata);

        return $metadata;
    }

    public function testTableAndColumns(): void
    {
        $metadata = $this->loadMetadata();

        $this->assertSame('ar_article_configuration', $metadata->getTableName());
        $this->assertEqualsCanonicalizing(
            ['id', 'articleId', 'templateKey', 'default', 'data'],
            $metadata->getFieldNames()
        );
        $this->assertSame('article_id', $metadata->getColumnName('articleId'));
        $this->assertSame('template_key', $metadata->getColumnName('templateKey'));
        $this->assertSame('is_default', $metadata->getColumnName('default'));
    }

    public function testDataIsNullableJson(): void
    {
        $mapping = $this->loadMetadata()->getFieldMapping('data');

        $this->assertSame('json', $mapping['type']);
        $this->assertTrue($mapping['nullable']);
    }

    public function testArticleIdIsUnique(): void
    {
        $this->assertTrue($this->loadMetadata()->getFieldMapping('articleId')['unique']);
    }

    public function testTemplateDefaultIndex(): void
    {
        $indexes = $this->loadMetadata()->table['indexes'];

        $this->assertSame(['template_key', 'is_default'], $indexes['idx_template_default']['columns']);
    }
}
