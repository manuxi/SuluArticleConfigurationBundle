<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Admin\FormMetadata;

use Manuxi\SuluArticleConfigurationBundle\Schema\ConfigurationSchema;
use Manuxi\SuluArticleConfigurationBundle\Schema\FieldDefinition;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataLoaderInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;

/**
 * Builds the form of the "Configuration" tab from the configured schema of an article template.
 */
class ArticleConfigurationFormMetadataLoader implements FormMetadataLoaderInterface
{
    public const KEY_PREFIX = 'article_configuration_';
    public const DEFAULT_SECTION = 'template_default';
    public const DEFAULT_FIELD = 'default';

    public function __construct(
        private readonly ConfigurationSchema $schema,
    ) {
    }

    public static function getFormKey(string $templateKey): string
    {
        return self::KEY_PREFIX . $templateKey;
    }

    public function getMetadata(string $key, string $locale, array $metadataOptions): ?MetadataInterface
    {
        if (!\str_starts_with($key, self::KEY_PREFIX) || \strlen($key) === \strlen(self::KEY_PREFIX)) {
            return null;
        }

        $templateKey = \substr($key, \strlen(self::KEY_PREFIX));

        $form = new FormMetadata();
        $form->setKey($key);
        $form->addItem($this->createDefaultSection($locale));

        $sections = [];
        foreach ($this->schema->getFields($templateKey) as $name => $field) {
            $sectionName = $field->getSection();
            if (!isset($sections[$sectionName])) {
                $section = new SectionMetadata($sectionName);
                $section->setLabel(FieldDefinition::TRANSLATION_ROOT . '.' . $sectionName, $locale);
                $sections[$sectionName] = $section;
                $form->addItem($section);
            }

            $sections[$sectionName]->addItem($this->createField($name, $field, $locale));
        }

        return $form;
    }

    private function createDefaultSection(string $locale): SectionMetadata
    {
        $section = new SectionMetadata(self::DEFAULT_SECTION);
        $section->setLabel(FieldDefinition::TRANSLATION_ROOT . '.' . self::DEFAULT_SECTION, $locale);

        $prefix = FieldDefinition::TRANSLATION_ROOT . '.' . self::DEFAULT_FIELD;
        $field = $this->createBaseField(self::DEFAULT_FIELD, 'checkbox', 6, $prefix, $locale);
        $field->addOption($this->createOption('type', 'toggler'));
        $field->addOption($this->createOption('default_value', 'false'));
        $section->addItem($field);

        return $section;
    }

    private function createField(string $name, FieldDefinition $field, string $locale): FieldMetadata
    {
        $metadata = $this->createBaseField(
            $name,
            $this->getFormType($field),
            $field->getColSpan(),
            $field->getTranslationPrefix(),
            $locale
        );

        if (null !== $field->getVisibleCondition()) {
            $metadata->setVisibleCondition($field->getVisibleCondition());
        }

        switch ($field->getType()) {
            case FieldDefinition::TYPE_TOGGLE:
                $metadata->addOption($this->createOption('type', 'toggler'));
                $metadata->addOption($this->createOption('default_value', $field->getDefault() ? 'true' : 'false'));
                break;
            case FieldDefinition::TYPE_SINGLE_SELECT:
                $metadata->addOption($this->createOption('default_value', (string) $field->getDefault()));
                $metadata->addOption($this->createSelectValues($field, $locale));
                break;
        }

        return $metadata;
    }

    private function createBaseField(string $name, string $type, ?int $colSpan, string $translationPrefix, string $locale): FieldMetadata
    {
        $metadata = new FieldMetadata($name);
        $metadata->setType($type);
        $metadata->setRequired(false);
        $metadata->setMultilingual(false);
        $metadata->setLabel($translationPrefix . '.title', $locale);
        $metadata->setDescription($translationPrefix . '.info', $locale);

        if (null !== $colSpan) {
            $metadata->setColSpan($colSpan);
        }

        return $metadata;
    }

    private function createSelectValues(FieldDefinition $field, string $locale): OptionMetadata
    {
        $values = new OptionMetadata();
        $values->setName('values');
        $values->setType(OptionMetadata::TYPE_COLLECTION);

        foreach ($field->getValues() as $value) {
            $option = new OptionMetadata();
            $option->setName($value);
            $option->setValue($value);
            $option->setTitle($field->getTranslationPrefix() . '.' . $value, $locale);
            $values->addValueOption($option);
        }

        return $values;
    }

    private function createOption(string $name, string $value): OptionMetadata
    {
        $option = new OptionMetadata();
        $option->setName($name);
        $option->setType(OptionMetadata::TYPE_STRING);
        $option->setValue($value);

        return $option;
    }

    private function getFormType(FieldDefinition $field): string
    {
        return match ($field->getType()) {
            FieldDefinition::TYPE_TOGGLE => 'checkbox',
            FieldDefinition::TYPE_TEXT => 'text_line',
            FieldDefinition::TYPE_NUMBER => 'number',
            FieldDefinition::TYPE_SINGLE_SELECT => 'single_select',
        };
    }
}
