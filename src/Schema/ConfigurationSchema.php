<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Schema;

use Manuxi\SuluArticleConfigurationBundle\Admin\FormMetadata\ArticleConfigurationFormComposer;

/**
 * The typed fields of an article template, derived from the composed XML form.
 */
class ConfigurationSchema
{
    /**
     * Stored in its own column, not part of the JSON data.
     */
    public const RESERVED_FIELD = 'default';

    /**
     * @var array<string, array<string, FieldDefinition>>
     */
    private array $cache = [];

    public function __construct(
        private readonly ArticleConfigurationFormComposer $composer,
    ) {
    }

    /**
     * @return array<string, FieldDefinition>
     */
    public function getFields(?string $templateKey = null): array
    {
        $cacheKey = $templateKey ?? '';
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $fields = [];
        foreach (MetadataReader::flattenFields($this->composer->compose($templateKey)->getItems()) as $name => $field) {
            if (self::RESERVED_FIELD === $name) {
                continue;
            }

            $fields[$name] = FieldDefinition::fromMetadata($field);
        }

        return $this->cache[$cacheKey] = $fields;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaults(?string $templateKey = null): array
    {
        $defaults = [];
        foreach ($this->getFields($templateKey) as $name => $field) {
            $defaults[$name] = $field->getDefault();
        }

        return $defaults;
    }

    /**
     * Keeps only fields known to the schema, casts them and fills missing ones with the default.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function sanitize(?string $templateKey, array $data): array
    {
        $result = [];
        foreach ($this->getFields($templateKey) as $name => $field) {
            $result[$name] = \array_key_exists($name, $data) ? $field->sanitize($data[$name]) : $field->getDefault();
        }

        return $result;
    }
}
