<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Schema;

use Manuxi\SuluArticleConfigurationBundle\Schema\ConfigurationSchema;
use Manuxi\SuluArticleConfigurationBundle\Schema\FieldDefinition;
use Manuxi\SuluArticleConfigurationBundle\Tests\Support\TestSchemaFactory;
use PHPUnit\Framework\TestCase;

class ConfigurationSchemaTest extends TestCase
{
    private ConfigurationSchema $schema;

    protected function setUp(): void
    {
        $this->schema = TestSchemaFactory::createSchema();
    }

    public function testBaseFieldsOfTheBundleForm(): void
    {
        $this->assertSame(
            [
                'layoutStyle', 'showToc', 'showReadingTime', 'showAuthorBox', 'showRelated', 'enableSidebar',
                'sidebarPosition', 'enableShareButtons', 'enablePrint', 'hidePublishDate', 'customCssClass',
            ],
            \array_keys($this->schema->getFields())
        );
    }

    public function testBaseDefaults(): void
    {
        $defaults = $this->schema->getDefaults();

        $this->assertSame('fullwidth', $defaults['layoutStyle']);
        $this->assertTrue($defaults['showToc']);
        $this->assertSame('right', $defaults['sidebarPosition']);
        $this->assertFalse($defaults['hidePublishDate']);
        $this->assertNull($defaults['customCssClass']);
    }

    public function testDefaultToggleIsNotPartOfTheData(): void
    {
        $this->assertArrayNotHasKey('default', $this->schema->getFields());
    }

    public function testTemplateWithoutOwnXmlUsesBase(): void
    {
        $this->assertSame($this->schema->getDefaults(), $this->schema->getDefaults('some_other_template'));
    }

    public function testBlogTemplateComposesBaseGroupAndTemplate(): void
    {
        $fields = $this->schema->getFields(TestSchemaFactory::BLOG_TEMPLATE);

        $this->assertArrayNotHasKey('showToc', $fields, 'removed by the template');
        $this->assertArrayHasKey('showSummary', $fields, 'added by the group');
        $this->assertArrayHasKey('heroVariant', $fields, 'added by the group');
        $this->assertArrayHasKey('readingSpeed', $fields, 'added by the template');
        $this->assertSame(['narrow', 'wide'], $fields['layoutStyle']->getValues());

        $defaults = $this->schema->getDefaults(TestSchemaFactory::BLOG_TEMPLATE);
        $this->assertSame('narrow', $defaults['layoutStyle']);
        $this->assertTrue($defaults['showSummary']);
        $this->assertSame('video', $defaults['heroVariant']);
        $this->assertSame(200, $defaults['readingSpeed']);
        $this->assertNull($defaults['gallery']);
    }

    public function testFieldKinds(): void
    {
        $fields = $this->schema->getFields(TestSchemaFactory::BLOG_TEMPLATE);

        $this->assertSame(FieldDefinition::KIND_NUMBER, $fields['readingSpeed']->getKind());
        $this->assertSame(FieldDefinition::KIND_RAW, $fields['gallery']->getKind());
        $this->assertSame(FieldDefinition::KIND_SINGLE_SELECT, $fields['heroVariant']->getKind());
        $this->assertSame(FieldDefinition::KIND_TOGGLE, $fields['showSummary']->getKind());
        $this->assertSame(FieldDefinition::KIND_TEXT, $fields['customCssClass']->getKind());
    }

    public function testSanitizeDropsUnknownKeysCastsValuesAndFillsDefaults(): void
    {
        $result = $this->schema->sanitize(null, [
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

    public function testSanitizeUsesTheSchemaOfTheTemplate(): void
    {
        $result = $this->schema->sanitize(TestSchemaFactory::BLOG_TEMPLATE, [
            'showToc' => false,
            'layoutStyle' => 'fullwidth',
            'heroVariant' => 'image',
            'readingSpeed' => '250',
            'gallery' => [['id' => 5]],
        ]);

        $this->assertArrayNotHasKey('showToc', $result);
        $this->assertSame('narrow', $result['layoutStyle'], 'fullwidth is not allowed for the template');
        $this->assertSame('image', $result['heroVariant']);
        $this->assertSame(250, $result['readingSpeed']);
        $this->assertSame([['id' => 5]], $result['gallery']);
    }
}
