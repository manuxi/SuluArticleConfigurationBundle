<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Schema;

final class FieldDefinition
{
    public const TYPE_TOGGLE = 'toggle';
    public const TYPE_TEXT = 'text';
    public const TYPE_SINGLE_SELECT = 'single_select';
    public const TYPE_NUMBER = 'number';

    public const TYPES = [
        self::TYPE_TOGGLE,
        self::TYPE_TEXT,
        self::TYPE_SINGLE_SELECT,
        self::TYPE_NUMBER,
    ];

    public const DEFAULT_SECTION = 'custom_options';
    public const TRANSLATION_ROOT = 'sulu_article_configuration';

    /**
     * @param list<string> $values
     */
    public function __construct(
        private readonly string $name,
        private readonly string $type,
        private readonly bool|int|float|string|null $default,
        private readonly array $values,
        private readonly string $section,
        private readonly ?int $colSpan,
        private readonly ?string $visibleCondition,
    ) {
    }

    /**
     * @param array<string, mixed> $definition
     */
    public static function fromArray(string $name, array $definition): self
    {
        $type = $definition['type'] ?? null;
        if (!\is_string($type) || !\in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException(\sprintf(
                'Field "%s" needs a valid type, one of: %s.',
                $name,
                \implode(', ', self::TYPES)
            ));
        }

        $values = \array_map('strval', \array_values($definition['values'] ?? []));
        if (self::TYPE_SINGLE_SELECT === $type && [] === $values) {
            throw new \InvalidArgumentException(\sprintf('Field "%s" of type "%s" needs at least one value.', $name, $type));
        }

        $default = $definition['default'] ?? null;
        if (null === $default) {
            $default = match ($type) {
                self::TYPE_TOGGLE => false,
                self::TYPE_SINGLE_SELECT => $values[0],
                default => null,
            };
        }

        $field = new self(
            $name,
            $type,
            $default,
            $values,
            (string) ($definition['section'] ?? self::DEFAULT_SECTION),
            isset($definition['colspan']) ? (int) $definition['colspan'] : null,
            isset($definition['visible_condition']) ? (string) $definition['visible_condition'] : null,
        );

        return new self(
            $name,
            $type,
            $field->sanitize($default),
            $values,
            $field->section,
            $field->colSpan,
            $field->visibleCondition,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDefault(): bool|int|float|string|null
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

    public function getSection(): string
    {
        return $this->section;
    }

    public function getColSpan(): ?int
    {
        return $this->colSpan;
    }

    public function getVisibleCondition(): ?string
    {
        return $this->visibleCondition;
    }

    public function getTranslationPrefix(): string
    {
        return self::TRANSLATION_ROOT . '.' . self::toSnakeCase($this->name);
    }

    public static function toSnakeCase(string $value): string
    {
        return \strtolower((string) \preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }

    /**
     * Casts a raw value to the type of this field. Invalid values fall back to the default.
     */
    public function sanitize(mixed $value): bool|int|float|string|null
    {
        return match ($this->type) {
            self::TYPE_TOGGLE => $this->sanitizeToggle($value),
            self::TYPE_TEXT => $this->sanitizeText($value),
            self::TYPE_NUMBER => $this->sanitizeNumber($value),
            self::TYPE_SINGLE_SELECT => $this->sanitizeSelect($value),
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

        $parsed = \filter_var($value, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE);

        return $parsed ?? (bool) $this->default;
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

        return \is_string($this->default) && \in_array($this->default, $this->values, true)
            ? $this->default
            : ($this->values[0] ?? null);
    }
}
