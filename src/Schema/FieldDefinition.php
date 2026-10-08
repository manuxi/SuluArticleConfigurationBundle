<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Schema;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;

/**
 * Describes how the value of one form field is typed, defaulted and cast. Derived from the Sulu form field.
 */
final class FieldDefinition
{
    public const KIND_TOGGLE = 'toggle';
    public const KIND_TEXT = 'text';
    public const KIND_NUMBER = 'number';
    public const KIND_SINGLE_SELECT = 'single_select';
    public const KIND_MULTI_SELECT = 'multi_select';
    public const KIND_RAW = 'raw';

    private const TEXT_TYPES = ['text_line', 'text_area', 'email', 'url', 'phone', 'color', 'date', 'time', 'datetime'];

    /**
     * @param list<string> $values
     */
    private function __construct(
        private readonly string $name,
        private readonly string $kind,
        private readonly mixed $default,
        private readonly array $values,
    ) {
    }

    public static function fromMetadata(FieldMetadata $field): self
    {
        $kind = self::resolveKind($field->getType());
        $values = self::readValues($field);
        $defaultOption = $field->findOption('default_value')?->getValue();
        $defaultOption = \is_scalar($defaultOption) ? (string) $defaultOption : null;

        $definition = new self($field->getName(), $kind, null, $values);

        return new self($field->getName(), $kind, $definition->resolveDefault($defaultOption), $values);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * @return list<string>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Casts a raw value to the type of this field. Invalid values fall back to the default.
     */
    public function sanitize(mixed $value): mixed
    {
        return match ($this->kind) {
            self::KIND_TOGGLE => $this->sanitizeToggle($value),
            self::KIND_TEXT => $this->sanitizeText($value),
            self::KIND_NUMBER => $this->sanitizeNumber($value),
            self::KIND_SINGLE_SELECT => $this->sanitizeSelect($value),
            self::KIND_MULTI_SELECT => $this->sanitizeMultiSelect($value),
            default => $this->sanitizeRaw($value),
        };
    }

    private static function resolveKind(string $type): string
    {
        return match (true) {
            'checkbox' === $type => self::KIND_TOGGLE,
            'number' === $type => self::KIND_NUMBER,
            'single_select' === $type => self::KIND_SINGLE_SELECT,
            'select' === $type => self::KIND_MULTI_SELECT,
            \in_array($type, self::TEXT_TYPES, true) => self::KIND_TEXT,
            default => self::KIND_RAW,
        };
    }

    /**
     * @return list<string>
     */
    private static function readValues(FieldMetadata $field): array
    {
        $option = $field->findOption('values');
        if (null === $option || !\is_array($option->getValue())) {
            return [];
        }

        $values = [];
        foreach ($option->getValue() as $valueOption) {
            if ($valueOption instanceof OptionMetadata && null !== $valueOption->getName()) {
                $values[] = (string) $valueOption->getName();
            }
        }

        return $values;
    }

    private function resolveDefault(?string $configured): mixed
    {
        return match ($this->kind) {
            self::KIND_TOGGLE => null !== $configured && \filter_var($configured, \FILTER_VALIDATE_BOOLEAN),
            self::KIND_TEXT => '' === $configured ? null : $configured,
            self::KIND_NUMBER => null !== $configured && \is_numeric($configured) ? $configured + 0 : null,
            self::KIND_SINGLE_SELECT => null !== $configured && \in_array($configured, $this->values, true)
                ? $configured
                : ($this->values[0] ?? null),
            self::KIND_MULTI_SELECT => [],
            default => null,
        };
    }

    private function sanitizeToggle(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (!\is_scalar($value)) {
            return (bool) $this->default;
        }

        return \filter_var($value, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE) ?? (bool) $this->default;
    }

    private function sanitizeText(mixed $value): ?string
    {
        if (!\is_string($value) && !\is_int($value) && !\is_float($value)) {
            return \is_string($this->default) ? $this->default : null;
        }

        $value = \trim((string) $value);

        return '' === $value ? null : $value;
    }

    private function sanitizeNumber(mixed $value): int|float|null
    {
        if (\is_int($value) || \is_float($value)) {
            return $value;
        }

        if (\is_string($value) && \is_numeric($value)) {
            return $value + 0;
        }

        return \is_int($this->default) || \is_float($this->default) ? $this->default : null;
    }

    private function sanitizeSelect(mixed $value): ?string
    {
        if (\is_scalar($value) && \in_array((string) $value, $this->values, true)) {
            return (string) $value;
        }

        return \is_string($this->default) ? $this->default : null;
    }

    /**
     * @return list<string>
     */
    private function sanitizeMultiSelect(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (\is_scalar($item) && \in_array((string) $item, $this->values, true) && !\in_array((string) $item, $result, true)) {
                $result[] = (string) $item;
            }
        }

        return $result;
    }

    private function sanitizeRaw(mixed $value): mixed
    {
        if (null === $value || \is_scalar($value)) {
            return $value;
        }

        if (\is_array($value)) {
            return \array_filter($value, static fn (mixed $item): bool => !\is_object($item) && !\is_resource($item));
        }

        return null;
    }
}
