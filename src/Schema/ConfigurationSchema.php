<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Schema;

use Manuxi\SuluArticleConfigurationBundle\Service\ArticleGroupProvider;

/**
 * Resolves the field schema of an article template: built-in fields, overridden by the "default" level of the
 * bundle configuration, then by the article group of the template, then by the template itself.
 */
class ConfigurationSchema
{
    /**
     * @var array<string, array<string, FieldDefinition>>
     */
    private array $cache = [];

    /**
     * @param array{default?: array<string, mixed>, groups?: array<string, mixed>, templates?: array<string, mixed>} $config
     */
    public function __construct(
        private readonly array $config,
        private readonly ArticleGroupProvider $groupProvider,
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

        $raw = $this->merge(DefaultFields::all(), $this->config['default']['fields'] ?? []);

        if (null !== $templateKey) {
            $group = $this->groupProvider->getGroupIdentifier($templateKey);
            if (null !== $group) {
                $raw = $this->merge($raw, $this->config['groups'][$group]['fields'] ?? []);
            }

            $raw = $this->merge($raw, $this->config['templates'][$templateKey]['fields'] ?? []);
        }

        $fields = [];
        foreach ($raw as $name => $definition) {
            $fields[$name] = FieldDefinition::fromArray($name, $definition);
        }

        return $this->cache[$cacheKey] = $fields;
    }

    /**
     * @return array<string, bool|int|float|string|null>
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
     * @return array<string, bool|int|float|string|null>
     */
    public function sanitize(?string $templateKey, array $data): array
    {
        $result = [];
        foreach ($this->getFields($templateKey) as $name => $field) {
            $result[$name] = \array_key_exists($name, $data) ? $field->sanitize($data[$name]) : $field->getDefault();
        }

        return $result;
    }

    /**
     * @param array<string, array<string, mixed>>   $base
     * @param array<string, array<string, mixed>|false> $override
     *
     * @return array<string, array<string, mixed>>
     */
    private function merge(array $base, array $override): array
    {
        foreach ($override as $name => $definition) {
            if (false === $definition) {
                unset($base[$name]);

                continue;
            }

            $base[$name] = \array_replace($base[$name] ?? [], $definition);
        }

        return $base;
    }
}
