<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Schema;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;

/**
 * Reads Sulu form metadata with the long-standing getters only. Methods such as FieldMetadata::hasTag() or
 * getFlatFieldMetadata() are missing or marked internal in some Sulu 3.0 releases.
 */
final class MetadataReader
{
    public static function hasTag(FieldMetadata $field, string $name): bool
    {
        foreach ($field->getTags() as $tag) {
            if ($tag->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    public static function findOption(FieldMetadata $field, string $name): ?OptionMetadata
    {
        foreach ($field->getOptions() as $option) {
            if ($option->getName() === $name) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @param array<array-key, mixed> $items items of a form or a section
     *
     * @return array<string, FieldMetadata> all fields of the tree by name
     */
    public static function flattenFields(array $items): array
    {
        $fields = [];
        foreach ($items as $item) {
            if ($item instanceof SectionMetadata) {
                foreach (self::flattenFields($item->getItems()) as $name => $field) {
                    $fields[$name] = $field;
                }
            } elseif ($item instanceof FieldMetadata) {
                $fields[$item->getName()] = $item;
            }
        }

        return $fields;
    }
}
