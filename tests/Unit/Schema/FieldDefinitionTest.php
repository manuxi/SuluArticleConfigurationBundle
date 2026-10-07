<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Schema;

use Manuxi\SuluArticleConfigurationBundle\Schema\FieldDefinition;
use PHPUnit\Framework\TestCase;

class FieldDefinitionTest extends TestCase
{
    public function testToggleDefaultsToFalse(): void
    {
        $field = FieldDefinition::fromArray('flag', ['type' => 'toggle']);

        $this->assertFalse($field->getDefault());
        $this->assertSame(FieldDefinition::DEFAULT_SECTION, $field->getSection());
    }

    public function testSelectDefaultsToFirstValue(): void
    {
        $field = FieldDefinition::fromArray('variant', ['type' => 'single_select', 'values' => ['a', 'b']]);

        $this->assertSame('a', $field->getDefault());
        $this->assertSame(['a', 'b'], $field->getValues());
    }

    public function testSelectWithInvalidDefaultFallsBackToFirstValue(): void
    {
        $field = FieldDefinition::fromArray('variant', ['type' => 'single_select', 'values' => ['a', 'b'], 'default' => 'x']);

        $this->assertSame('a', $field->getDefault());
    }

    public function testMissingTypeIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FieldDefinition::fromArray('broken', ['default' => true]);
    }

    public function testSelectWithoutValuesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FieldDefinition::fromArray('broken', ['type' => 'single_select']);
    }

    /**
     * @dataProvider toggleProvider
     */
    public function testSanitizeToggle(mixed $input, bool $expected): void
    {
        $field = FieldDefinition::fromArray('flag', ['type' => 'toggle', 'default' => true]);

        $this->assertSame($expected, $field->sanitize($input));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function toggleProvider(): iterable
    {
        yield 'true' => [true, true];
        yield 'false' => [false, false];
        yield 'string true' => ['true', true];
        yield 'string zero' => ['0', false];
        yield 'garbage falls back to default' => ['maybe', true];
        yield 'null falls back to default' => [null, true];
    }

    public function testSanitizeSelectRejectsUnknownValue(): void
    {
        $field = FieldDefinition::fromArray('variant', ['type' => 'single_select', 'values' => ['a', 'b'], 'default' => 'b']);

        $this->assertSame('a', $field->sanitize('a'));
        $this->assertSame('b', $field->sanitize('unknown'));
        $this->assertSame('b', $field->sanitize(['a']));
    }

    public function testSanitizeText(): void
    {
        $field = FieldDefinition::fromArray('css', ['type' => 'text']);

        $this->assertSame('foo', $field->sanitize('  foo '));
        $this->assertNull($field->sanitize('   '));
        $this->assertNull($field->sanitize(null));
        $this->assertNull($field->sanitize(['x']));
    }

    public function testSanitizeNumber(): void
    {
        $field = FieldDefinition::fromArray('limit', ['type' => 'number', 'default' => 5]);

        $this->assertSame(10, $field->sanitize('10'));
        $this->assertSame(1.5, $field->sanitize('1.5'));
        $this->assertSame(7, $field->sanitize(7));
        $this->assertSame(5, $field->sanitize('abc'));
    }

    public function testTranslationPrefixIsSnakeCase(): void
    {
        $field = FieldDefinition::fromArray('showReadingTime', ['type' => 'toggle']);

        $this->assertSame('sulu_article_configuration.show_reading_time', $field->getTranslationPrefix());
    }
}
