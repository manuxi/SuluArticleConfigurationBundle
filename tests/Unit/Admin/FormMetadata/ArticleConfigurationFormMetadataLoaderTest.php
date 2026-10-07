<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Admin\FormMetadata;

use Manuxi\SuluArticleConfigurationBundle\Admin\FormMetadata\ArticleConfigurationFormMetadataLoader;
use Manuxi\SuluArticleConfigurationBundle\Schema\ConfigurationSchema;
use Manuxi\SuluArticleConfigurationBundle\Service\ArticleGroupProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;

class ArticleConfigurationFormMetadataLoaderTest extends TestCase
{
    private function createLoader(): ArticleConfigurationFormMetadataLoader
    {
        $groupProvider = $this->createMock(ArticleGroupProvider::class);
        $groupProvider->method('getGroupIdentifier')->willReturn('blog');

        $schema = new ConfigurationSchema([
            'groups' => ['blog' => ['fields' => [
                'heroVariant' => ['type' => 'single_select', 'values' => ['image', 'video'], 'default' => 'video', 'section' => 'hero'],
                'readingSpeed' => ['type' => 'number', 'default' => 200],
            ]]],
            'templates' => ['blog_post' => ['fields' => ['showToc' => false]]],
        ], $groupProvider);

        return new ArticleConfigurationFormMetadataLoader($schema);
    }

    public function testIgnoresForeignKeys(): void
    {
        $loader = $this->createLoader();

        $this->assertNull($loader->getMetadata('article', 'en', []));
        $this->assertNull($loader->getMetadata('contact_details', 'en', []));
        $this->assertNull($loader->getMetadata(ArticleConfigurationFormMetadataLoader::KEY_PREFIX, 'en', []));
    }

    public function testFormKey(): void
    {
        $this->assertSame('article_configuration_blog_post', ArticleConfigurationFormMetadataLoader::getFormKey('blog_post'));
    }

    public function testBuildsSectionsInSchemaOrder(): void
    {
        $form = $this->createLoader()->getMetadata('article_configuration_blog_post', 'en', []);

        $this->assertInstanceOf(FormMetadata::class, $form);
        $this->assertSame('article_configuration_blog_post', $form->getKey());
        $this->assertSame(
            ['template_default', 'display_options', 'sidebar_options', 'features', 'publication_settings', 'styling', 'hero', 'custom_options'],
            \array_keys($form->getItems())
        );
    }

    public function testRemovedFieldIsNotPartOfTheForm(): void
    {
        $form = $this->createLoader()->getMetadata('article_configuration_blog_post', 'en', []);
        $display = $form->getItems()['display_options'];

        $this->assertInstanceOf(SectionMetadata::class, $display);
        $this->assertArrayNotHasKey('showToc', $display->getItems());
        $this->assertArrayHasKey('showReadingTime', $display->getItems());
    }

    public function testDefaultToggleIsAlwaysFirst(): void
    {
        $form = $this->createLoader()->getMetadata('article_configuration_blog_post', 'en', []);
        $field = $form->getItems()['template_default']->getItems()['default'];

        $this->assertSame('checkbox', $field->getType());
        $this->assertSame('toggler', $field->findOption('type')->getValue());
        $this->assertSame('sulu_article_configuration.default.title', $field->getLabel('en'));
    }

    public function testToggleField(): void
    {
        $form = $this->createLoader()->getMetadata('article_configuration_blog_post', 'en', []);
        $field = $form->getItems()['display_options']->getItems()['showReadingTime'];

        $this->assertInstanceOf(FieldMetadata::class, $field);
        $this->assertSame('checkbox', $field->getType());
        $this->assertSame(3, $field->getColSpan());
        $this->assertSame('toggler', $field->findOption('type')->getValue());
        $this->assertSame('true', $field->findOption('default_value')->getValue());
        $this->assertSame('sulu_article_configuration.show_reading_time.title', $field->getLabel('en'));
        $this->assertSame('sulu_article_configuration.show_reading_time.info', $field->getDescription('en'));
    }

    public function testSelectFieldWithValuesAndVisibleCondition(): void
    {
        $form = $this->createLoader()->getMetadata('article_configuration_blog_post', 'en', []);
        $field = $form->getItems()['sidebar_options']->getItems()['sidebarPosition'];

        $this->assertSame('single_select', $field->getType());
        $this->assertSame('enableSidebar == true', $field->getVisibleCondition());
        $this->assertSame('right', $field->findOption('default_value')->getValue());

        $values = $field->findOption('values')->getValue();
        $this->assertSame(['left', 'right'], \array_map(static fn ($option) => $option->getName(), $values));
        $this->assertSame('sulu_article_configuration.sidebar_position.left', $values[0]->getTitle('en'));
    }

    public function testCustomFieldsAndSections(): void
    {
        $form = $this->createLoader()->getMetadata('article_configuration_blog_post', 'en', []);

        $hero = $form->getItems()['hero']->getItems()['heroVariant'];
        $this->assertSame('video', $hero->findOption('default_value')->getValue());
        $this->assertSame('sulu_article_configuration.hero', $form->getItems()['hero']->getLabel('en'));

        $speed = $form->getItems()['custom_options']->getItems()['readingSpeed'];
        $this->assertSame('number', $speed->getType());
    }

    public function testTextField(): void
    {
        $form = $this->createLoader()->getMetadata('article_configuration_blog_post', 'en', []);

        $this->assertSame('text_line', $form->getItems()['styling']->getItems()['customCssClass']->getType());
    }
}
