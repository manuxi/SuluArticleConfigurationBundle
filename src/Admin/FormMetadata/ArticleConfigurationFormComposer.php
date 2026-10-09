<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Admin\FormMetadata;

use Manuxi\SuluArticleConfigurationBundle\Schema\MetadataReader;
use Manuxi\SuluArticleConfigurationBundle\Service\ArticleGroupProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataLoaderInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SchemaMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;

/**
 * Composes the form of an article template from the XML forms of the levels base, article group and template.
 * A later level adds sections and fields, replaces fields of the same name and removes fields tagged
 * "article_configuration.remove". Every level is optional.
 */
class ArticleConfigurationFormComposer
{
    public const BASE_KEY = 'article_configuration';
    public const GROUP_KEY_PREFIX = 'article_configuration_group_';
    public const TEMPLATE_KEY_PREFIX = 'article_configuration_template_';
    public const REMOVE_TAG = 'article_configuration.remove';

    /**
     * @var array<string, FormMetadata>
     */
    private array $cache = [];

    public function __construct(
        private readonly FormMetadataLoaderInterface $xmlFormMetadataLoader,
        private readonly ArticleGroupProvider $groupProvider,
        private readonly ?SchemaMetadataProvider $schemaMetadataProvider = null,
    ) {
    }

    public function compose(?string $templateKey = null, string $locale = ''): FormMetadata
    {
        $cacheKey = ($templateKey ?? '') . '|' . $locale;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $keys = [self::BASE_KEY];
        if (null !== $templateKey) {
            $group = $this->groupProvider->getGroupIdentifier($templateKey);
            if (null !== $group) {
                $keys[] = self::GROUP_KEY_PREFIX . $group;
            }
            $keys[] = self::TEMPLATE_KEY_PREFIX . $templateKey;
        }

        /** @var array<string, SectionNode|FieldMetadata> $nodes */
        $nodes = [];
        foreach ($keys as $key) {
            $level = $this->xmlFormMetadataLoader->getMetadata($key, $locale, []);
            if ($level instanceof FormMetadata) {
                $this->applyLevel($nodes, $level);
            }
        }

        $form = new FormMetadata();
        $form->setKey(null === $templateKey ? self::BASE_KEY : self::TEMPLATE_KEY_PREFIX . $templateKey);
        foreach ($nodes as $node) {
            if ($node instanceof FieldMetadata) {
                $form->addItem($node);
            } elseif (!$node->isEmpty()) {
                $form->addItem($node->toMetadata());
            }
        }

        if (null !== $this->schemaMetadataProvider) {
            $form->setSchema($this->schemaMetadataProvider->getMetadata(\array_values($form->getItems())));
        }

        return $this->cache[$cacheKey] = $form;
    }

    /**
     * @param array<string, SectionNode|FieldMetadata> $nodes
     */
    private function applyLevel(array &$nodes, FormMetadata $level): void
    {
        foreach (MetadataReader::flattenFields($level->getItems()) as $field) {
            if (MetadataReader::hasTag($field, self::REMOVE_TAG)) {
                $this->removeField($nodes, $field->getName());
            }
        }

        $this->mergeItems($nodes, $nodes, $level->getItems());
    }

    /**
     * Field names are unique across the whole form, so a field is replaced wherever it lives in the tree.
     *
     * @param array<string, SectionNode|FieldMetadata> $root
     * @param array<string, SectionNode|FieldMetadata> $target
     * @param array<array-key, mixed>                  $items
     */
    private function mergeItems(array &$root, array &$target, array $items): void
    {
        foreach ($items as $item) {
            if ($item instanceof SectionMetadata) {
                $node = $target[$item->getName()] ?? null;
                if ($node instanceof SectionNode) {
                    $node->applyOverrides($item);
                } else {
                    $node = new SectionNode($item);
                    $target[$item->getName()] = $node;
                }

                $this->mergeItems($root, $node->getChildren(), $item->getItems());

                continue;
            }

            if (!$item instanceof FieldMetadata || MetadataReader::hasTag($item, self::REMOVE_TAG)) {
                continue;
            }

            if (!$this->replaceField($root, $item)) {
                $target[$item->getName()] = $item;
            }
        }
    }

    /**
     * @param array<string, SectionNode|FieldMetadata> $nodes
     */
    private function replaceField(array &$nodes, FieldMetadata $field): bool
    {
        foreach ($nodes as $key => $node) {
            if ($node instanceof FieldMetadata) {
                if ($node->getName() === $field->getName()) {
                    $nodes[$key] = $field;

                    return true;
                }

                continue;
            }

            if ($this->replaceField($node->getChildren(), $field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, SectionNode|FieldMetadata> $nodes
     */
    private function removeField(array &$nodes, string $name): void
    {
        foreach ($nodes as $key => $node) {
            if ($node instanceof FieldMetadata) {
                if ($node->getName() === $name) {
                    unset($nodes[$key]);
                }

                continue;
            }

            $this->removeField($node->getChildren(), $name);
        }
    }
}
