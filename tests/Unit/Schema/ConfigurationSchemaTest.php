<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Schema;

use Manuxi\SuluArticleConfigurationBundle\Schema\ConfigurationSchema;
use Manuxi\SuluArticleConfigurationBundle\Service\ArticleGroupProvider;
use PHPUnit\Framework\TestCase;

class ConfigurationSchemaTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     */
    private function createSchema(array $config): ConfigurationSchema
    {
        $groupProvider = $this->createMock(ArticleGroupProvider::class);
        $groupProvider->method('getGroupIdentifier')->willReturnMap([
            ['blog_post', 'blog'],
            ['page_simple', 'default'],
        ]);

        return new ConfigurationSchema($config, $groupProvider);
    }

    public function testBuiltInFieldsWithoutConfiguration(): void
    {
        $schema = $this->createSchema([]);

        $this->assertSame(
            [
                'layoutStyle', 'showToc', 'showReadingTime', 'showAuthorBox', 'showRelated', 'enableSidebar',
                'sidebarPosition', 'enableShareButtons', 'enablePrint', 'hidePublishDate', 'customCssClass',
            ],
            \array_keys($schema->getFields())
        );
        $this->assertSame('fullwidth', $schema->getDefaults()['layoutStyle']);
        $this->assertTrue($schema->getDefaults()['showToc']);
        $this->assertNull($schema->getDefaults()['customCssClass']);
    }

    public function testDefaultLevelOverridesBuiltIn(): void
    {
        $schema = $this->createSchema([
            'default' => ['fields' => ['layoutStyle' => ['default' => 'narrow'], 'customCssClass' => false]],
        ]);

        $this->assertSame('narrow', $schema->getDefaults()['layoutStyle']);
        $this->assertArrayNotHasKey('customCssClass', $schema->getFields());
        $this->assertSame(['default', 'wide', 'fullwidth', 'narrow'], $schema->getFields()['layoutStyle']->getValues());
    }

    public function testGroupLevelAddsFieldsOnlyForTemplatesOfThatGroup(): void
    {
        $schema = $this->createSchema([
            'groups' => ['blog' => ['fields' => ['heroVariant' => ['type' => 'single_select', 'values' => ['image', 'video']]]]],
        ]);

        $this->assertArrayHasKey('heroVariant', $schema->getFields('blog_post'));
        $this->assertArrayNotHasKey('heroVariant', $schema->getFields('page_simple'));
        $this->assertArrayNotHasKey('heroVariant', $schema->getFields());
    }

    public function testTemplateLevelWinsOverGroupLevel(): void
    {
        $schema = $this->createSchema([
            'groups' => ['blog' => ['fields' => ['showToc' => ['default' => false], 'showRelated' => false]]],
            'templates' => ['blog_post' => ['fields' => [
                'showToc' => ['default' => true],
                'layoutStyle' => ['values' => ['narrow', 'wide'], 'default' => 'narrow'],
            ]]],
        ]);

        $fields = $schema->getFields('blog_post');
        $this->assertTrue($fields['showToc']->getDefault());
        $this->assertArrayNotHasKey('showRelated', $fields);
        $this->assertSame(['narrow', 'wide'], $fields['layoutStyle']->getValues());
        $this->assertSame('narrow', $fields['layoutStyle']->getDefault());
    }

    public function testUnknownTemplateUsesDefaultLevelOnly(): void
    {
        $schema = $this->createSchema(['templates' => ['blog_post' => ['fields' => ['showToc' => false]]]]);

        $this->assertArrayHasKey('showToc', $schema->getFields('other_template'));
    }

    public function testNewFieldWithoutTypeIsRejected(): void
    {
        $schema = $this->createSchema(['templates' => ['blog_post' => ['fields' => ['broken' => ['default' => 1]]]]]);

        $this->expectException(\InvalidArgumentException::class);

        $schema->getFields('blog_post');
    }

    public function testSanitizeDropsUnknownKeysCastsValuesAndFillsDefaults(): void
    {
        $schema = $this->createSchema([]);

        $result = $schema->sanitize(null, [
            'layoutStyle' => 'invalid',
            'showToc' => 'false',
            'unknownKey' => 'x',
            'customCssClass' => ' my-class ',
        ]);

        $this->assertArrayNotHasKey('unknownKey', $result);
        $this->assertSame('fullwidth', $result['layoutStyle']);
        $this->assertFalse($result['showToc']);
        $this->assertTrue($result['showRelated']);
        $this->assertSame('my-class', $result['customCssClass']);
    }
}
