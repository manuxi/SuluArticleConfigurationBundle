<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Admin\FormMetadata;

use Manuxi\SuluArticleConfigurationBundle\Admin\FormMetadata\ArticleConfigurationFormComposer;
use Manuxi\SuluArticleConfigurationBundle\Service\ArticleGroupProvider;
use Manuxi\SuluArticleConfigurationBundle\Tests\Support\TestSchemaFactory;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataLoaderInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;

class ArticleConfigurationFormComposerTest extends TestCase
{
    /**
     * @param array<string, FormMetadata> $forms
     */
    private function createComposer(array $forms, ?string $group = 'blog'): ArticleConfigurationFormComposer
    {
        $loader = new class($forms) implements FormMetadataLoaderInterface {
            /**
             * @param array<string, FormMetadata> $forms
             */
            public function __construct(private array $forms)
            {
            }

            public function getMetadata(string $key, string $locale, array $metadataOptions): ?MetadataInterface
            {
                return $this->forms[$key] ?? null;
            }
        };

        $groupProvider = $this->createMock(ArticleGroupProvider::class);
        $groupProvider->method('getGroupIdentifier')->willReturn($group);

        return new ArticleConfigurationFormComposer($loader, $groupProvider, TestSchemaFactory::createSchemaMetadataProvider());
    }

    /**
     * @param array<string, list<FieldMetadata>> $sections
     */
    private function createForm(string $key, array $sections): FormMetadata
    {
        $form = new FormMetadata();
        $form->setKey($key);
        foreach ($sections as $name => $fields) {
            $form->addItem($this->createSection($name, $fields));
        }

        return $form;
    }

    /**
     * @param list<FieldMetadata> $fields
     */
    private function createSection(string $name, array $fields, ?string $label = null): SectionMetadata
    {
        $section = new SectionMetadata($name);
        if (null !== $label) {
            $section->setLabel($label, 'en');
        }
        foreach ($fields as $field) {
            $section->addItem($field);
        }

        return $section;
    }

    private function createField(string $name, string $type = 'checkbox', bool $remove = false): FieldMetadata
    {
        $field = new FieldMetadata($name);
        $field->setType($type);
        if ($remove) {
            $tag = new TagMetadata();
            $tag->setName(ArticleConfigurationFormComposer::REMOVE_TAG);
            $field->addTag($tag);
        }

        return $field;
    }

    /**
     * @return list<string>
     */
    private function fieldNames(FormMetadata $form): array
    {
        return \array_keys($form->getFlatFieldMetadata());
    }

    public function testBaseOnlyWithoutTemplate(): void
    {
        $composer = $this->createComposer([
            'article_configuration' => $this->createForm('article_configuration', ['display' => [$this->createField('a'), $this->createField('b')]]),
        ]);

        $form = $composer->compose();

        $this->assertSame('article_configuration', $form->getKey());
        $this->assertSame(['a', 'b'], $this->fieldNames($form));
    }

    public function testMissingLevelsAreOptional(): void
    {
        $composer = $this->createComposer([
            'article_configuration' => $this->createForm('article_configuration', ['display' => [$this->createField('a')]]),
        ]);

        $form = $composer->compose('blog_post');

        $this->assertSame('article_configuration_template_blog_post', $form->getKey());
        $this->assertSame(['a'], $this->fieldNames($form));
    }

    public function testNoLevelAtAllYieldsEmptyForm(): void
    {
        $this->assertSame([], $this->createComposer([])->compose('blog_post')->getItems());
    }

    public function testGroupAddsFieldToExistingSectionAndNewSection(): void
    {
        $composer = $this->createComposer([
            'article_configuration' => $this->createForm('article_configuration', ['display' => [$this->createField('a')]]),
            'article_configuration_group_blog' => $this->createForm('x', [
                'display' => [$this->createField('b')],
                'hero' => [$this->createField('hero')],
            ]),
        ]);

        $form = $composer->compose('blog_post');

        $this->assertSame(['display', 'hero'], \array_keys($form->getItems()));
        $this->assertSame(['a', 'b'], \array_keys($form->getItems()['display']->getItems()));
        $this->assertSame(['hero'], \array_keys($form->getItems()['hero']->getItems()));
    }

    public function testGroupIsSkippedForTemplatesWithoutGroup(): void
    {
        $composer = $this->createComposer([
            'article_configuration' => $this->createForm('article_configuration', ['display' => [$this->createField('a')]]),
            'article_configuration_group_blog' => $this->createForm('x', ['display' => [$this->createField('b')]]),
        ], null);

        $this->assertSame(['a'], $this->fieldNames($composer->compose('blog_post')));
    }

    public function testTemplateReplacesFieldInPlaceEvenFromAnotherSection(): void
    {
        $original = $this->createField('layout', 'single_select');
        $replacement = $this->createField('layout', 'text_line');

        $composer = $this->createComposer([
            'article_configuration' => $this->createForm('article_configuration', ['display' => [$this->createField('a'), $original, $this->createField('c')]]),
            'article_configuration_template_blog_post' => $this->createForm('x', ['other' => [$replacement]]),
        ]);

        $form = $composer->compose('blog_post');

        $this->assertSame(['display'], \array_keys($form->getItems()));
        $this->assertSame(['a', 'layout', 'c'], \array_keys($form->getItems()['display']->getItems()));
        $this->assertSame($replacement, $form->getItems()['display']->getItems()['layout']);
    }

    public function testRemoveTagRemovesFieldAndKeepsOthers(): void
    {
        $composer = $this->createComposer([
            'article_configuration' => $this->createForm('article_configuration', ['display' => [$this->createField('a'), $this->createField('b')]]),
            'article_configuration_template_blog_post' => $this->createForm('x', ['display' => [$this->createField('a', 'checkbox', true)]]),
        ]);

        $this->assertSame(['b'], $this->fieldNames($composer->compose('blog_post')));
    }

    public function testRemovedFieldCanBeReAddedByALaterLevel(): void
    {
        $composer = $this->createComposer([
            'article_configuration' => $this->createForm('article_configuration', ['display' => [$this->createField('a')]]),
            'article_configuration_group_blog' => $this->createForm('x', ['display' => [$this->createField('a', 'checkbox', true)]]),
            'article_configuration_template_blog_post' => $this->createForm('y', ['display' => [$this->createField('a', 'text_line')]]),
        ]);

        $form = $composer->compose('blog_post');

        $this->assertSame(['a'], $this->fieldNames($form));
        $this->assertSame('text_line', $form->getFlatFieldMetadata()['a']->getType());
    }

    public function testSectionWithOnlyRemovedFieldsDisappears(): void
    {
        $composer = $this->createComposer([
            'article_configuration' => $this->createForm('article_configuration', [
                'display' => [$this->createField('a')],
                'extra' => [$this->createField('b')],
            ]),
            'article_configuration_template_blog_post' => $this->createForm('x', ['extra' => [$this->createField('b', 'checkbox', true)]]),
        ]);

        $this->assertSame(['display'], \array_keys($composer->compose('blog_post')->getItems()));
    }

    public function testRemovingUnknownFieldIsIgnoredAndDoesNotCreateSection(): void
    {
        $composer = $this->createComposer([
            'article_configuration' => $this->createForm('article_configuration', ['display' => [$this->createField('a')]]),
            'article_configuration_template_blog_post' => $this->createForm('x', ['ghost' => [$this->createField('nope', 'checkbox', true)]]),
        ]);

        $form = $composer->compose('blog_post');

        $this->assertSame(['display'], \array_keys($form->getItems()));
    }

    public function testSectionLabelOfLaterLevelWinsOtherwiseKept(): void
    {
        $composer = $this->createComposer([
            'article_configuration' => $this->createForm('article_configuration', []),
            'article_configuration_group_blog' => (function () {
                $form = new FormMetadata();
                $form->addItem($this->createSection('display', [$this->createField('a')], 'Base label'));

                return $form;
            })(),
            'article_configuration_template_blog_post' => (function () {
                $form = new FormMetadata();
                $form->addItem($this->createSection('display', [$this->createField('b')], 'Template label'));

                return $form;
            })(),
        ]);

        $this->assertSame('Template label', $composer->compose('blog_post')->getItems()['display']->getLabel('en'));

        $keepComposer = $this->createComposer([
            'article_configuration' => (function () {
                $form = new FormMetadata();
                $form->addItem($this->createSection('display', [$this->createField('a')], 'Base label'));

                return $form;
            })(),
            'article_configuration_template_blog_post' => $this->createForm('x', ['display' => [$this->createField('b')]]),
        ]);

        $this->assertSame('Base label', $keepComposer->compose('blog_post')->getItems()['display']->getLabel('en'));
    }

    public function testResultIsCachedPerTemplate(): void
    {
        $loader = $this->createMock(FormMetadataLoaderInterface::class);
        $loader->expects($this->exactly(3))->method('getMetadata')->willReturn(null);
        $groupProvider = $this->createMock(ArticleGroupProvider::class);
        $groupProvider->method('getGroupIdentifier')->willReturn('blog');

        $composer = new ArticleConfigurationFormComposer($loader, $groupProvider);

        $this->assertSame($composer->compose('blog_post'), $composer->compose('blog_post'));
    }

    public function testSchemaIsBuiltFromTheComposedFieldsOnly(): void
    {
        $required = $this->createField('headline', 'text_line');
        $required->setRequired(true);
        $removedRequired = $this->createField('legacy', 'text_line');
        $removedRequired->setRequired(true);

        $composer = $this->createComposer([
            'article_configuration' => $this->createForm('article_configuration', ['display' => [$required, $removedRequired]]),
            'article_configuration_template_blog_post' => $this->createForm('x', ['display' => [$this->createField('legacy', 'text_line', true)]]),
        ]);

        $schema = $composer->compose('blog_post')->getSchema()->toJsonSchema();

        $this->assertSame(['headline'], $schema['required'] ?? []);
    }
}
