<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\DependencyInjection;

use Manuxi\SuluArticleConfigurationBundle\Schema\FieldDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private const FIELD_KEYS = ['type', 'default', 'values', 'section', 'colspan', 'visible_condition'];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_article_configuration');
        $root = $treeBuilder->getRootNode();

        $root
            ->children()
                ->arrayNode('default')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->append($this->fieldsNode())
                    ->end()
                ->end()
                ->arrayNode('groups')
                    ->useAttributeAsKey('name', false)
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->children()
                            ->append($this->fieldsNode())
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('templates')
                    ->useAttributeAsKey('name', false)
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->children()
                            ->append($this->fieldsNode())
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    private function fieldsNode(): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition('fields');
        $node
            ->useAttributeAsKey('name', false)
            ->normalizeKeys(false)
            ->variablePrototype()
                ->validate()
                    ->ifTrue(static fn ($value): bool => false !== $value && !\is_array($value))
                    ->thenInvalid('A field must be false (remove the field) or a map of field options.')
                ->end()
                ->validate()
                    ->ifTrue(static fn ($value): bool => \is_array($value) && [] !== \array_diff(\array_keys($value), self::FIELD_KEYS))
                    ->then(static function (array $value): never {
                        throw new \InvalidArgumentException(\sprintf(
                            'Unknown field option(s) "%s". Allowed: %s.',
                            \implode('", "', \array_diff(\array_keys($value), self::FIELD_KEYS)),
                            \implode(', ', self::FIELD_KEYS)
                        ));
                    })
                ->end()
                ->validate()
                    ->ifTrue(static fn ($value): bool => \is_array($value) && isset($value['type']) && !\in_array($value['type'], FieldDefinition::TYPES, true))
                    ->then(static function (array $value): never {
                        throw new \InvalidArgumentException(\sprintf(
                            'Invalid field type "%s". Allowed: %s.',
                            \is_scalar($value['type']) ? $value['type'] : \gettype($value['type']),
                            \implode(', ', FieldDefinition::TYPES)
                        ));
                    })
                ->end()
            ->end();

        return $node;
    }
}
