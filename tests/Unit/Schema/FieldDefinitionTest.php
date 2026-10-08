<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Schema;

use Manuxi\SuluArticleConfigurationBundle\Schema\FieldDefinition;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;

class FieldDefinitionTest extends TestCase
{
    /**
     * @param list<string>|null $values
     */
    private function createField(string $type, ?string $default = null, ?array $values = null): FieldMetadata
    {
        $field = new FieldMetadata('field');
        $field->setType($type);

        if (null !== $default) {
            $option = new OptionMetadata();
            $option->setName('default_value');
            $option->setType(OptionMetadata::TYPE_STRING);
            $option->setValue($default);
            $field->addOption($option);
        }

        if (null !== $values) {
            $collection = new OptionMetadata();
            $collection->setName('values');
            $collection->setType(OptionMetadata::TYPE_COLLECTION);
            foreach ($values as $value) {
                $valueOption = new OptionMetadata();
                $valueOption->setName($value);
                $valueOption->setValue($value);
                $collection->addValueOption($valueOption);
            }
            $field->addOption($collection);
        }

        return $field;
    }

    /**
     * @dataProvider kindProvider
     */
    public function testKindIsDerivedFromTheFieldType(string $type, string $expected): void
    {
        $this->assertSame($expected, FieldDefinition::fromMetadata($this->createField($type))->getKind());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function kindProvider(): iterable
    {
        yield 'checkbox' => ['checkbox', FieldDefinition::KIND_TOGGLE];
        yield 'number' => ['number', FieldDefinition::KIND_NUMBER];
        yield 'single_select' => ['single_select', FieldDefinition::KIND_SINGLE_SELECT];
        yield 'select' => ['select', FieldDefinition::KIND_MULTI_SELECT];
        yield 'text_line' => ['text_line', FieldDefinition::KIND_TEXT];
        yield 'text_area' => ['text_area', FieldDefinition::KIND_TEXT];
        yield 'email' => ['email', FieldDefinition::KIND_TEXT];
        yield 'color' => ['color', FieldDefinition::KIND_TEXT];
        yield 'media_selection' => ['media_selection', FieldDefinition::KIND_RAW];
        yield 'unknown custom type' => ['my_custom_type', FieldDefinition::KIND_RAW];
    }

    public function testToggleDefaults(): void
    {
        $this->assertFalse(FieldDefinition::fromMetadata($this->createField('checkbox'))->getDefault());
        $this->assertTrue(FieldDefinition::fromMetadata($this->createField('checkbox', 'true'))->getDefault());
        $this->assertFalse(FieldDefinition::fromMetadata($this->createField('checkbox', 'false'))->getDefault());
    }

    public function testTextAndNumberDefaults(): void
    {
        $this->assertNull(FieldDefinition::fromMetadata($this->createField('text_line'))->getDefault());
        $this->assertSame('x', FieldDefinition::fromMetadata($this->createField('text_line', 'x'))->getDefault());
        $this->assertNull(FieldDefinition::fromMetadata($this->createField('number'))->getDefault());
        $this->assertSame(200, FieldDefinition::fromMetadata($this->createField('number', '200'))->getDefault());
        $this->assertSame(1.5, FieldDefinition::fromMetadata($this->createField('number', '1.5'))->getDefault());
        $this->assertNull(FieldDefinition::fromMetadata($this->createField('number', 'abc'))->getDefault());
    }

    public function testSelectValuesAndDefault(): void
    {
        $field = FieldDefinition::fromMetadata($this->createField('single_select', 'b', ['a', 'b']));

        $this->assertSame(['a', 'b'], $field->getValues());
        $this->assertSame('b', $field->getDefault());
    }

    public function testSelectDefaultFallsBackToFirstValue(): void
    {
        $this->assertSame('a', FieldDefinition::fromMetadata($this->createField('single_select', null, ['a', 'b']))->getDefault());
        $this->assertSame('a', FieldDefinition::fromMetadata($this->createField('single_select', 'x', ['a', 'b']))->getDefault());
    }

    public function testSelectWithoutValuesHasNoDefault(): void
    {
        $this->assertNull(FieldDefinition::fromMetadata($this->createField('single_select'))->getDefault());
    }

    public function testMultiSelectDefaultsToEmptyList(): void
    {
        $this->assertSame([], FieldDefinition::fromMetadata($this->createField('select', null, ['a']))->getDefault());
    }

    /**
     * @dataProvider toggleProvider
     */
    public function testSanitizeToggle(mixed $input, bool $expected): void
    {
        $field = FieldDefinition::fromMetadata($this->createField('checkbox', 'true'));

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
        yield 'array falls back to default' => [[1], true];
    }

    public function testSanitizeSelectRejectsUnknownValue(): void
    {
        $field = FieldDefinition::fromMetadata($this->createField('single_select', 'b', ['a', 'b']));

        $this->assertSame('a', $field->sanitize('a'));
        $this->assertSame('b', $field->sanitize('unknown'));
        $this->assertSame('b', $field->sanitize(['a']));
    }

    public function testSanitizeMultiSelectKeepsOnlyAllowedUniqueValues(): void
    {
        $field = FieldDefinition::fromMetadata($this->createField('select', null, ['a', 'b', 'c']));

        $this->assertSame(['a', 'c'], $field->sanitize(['a', 'x', 'c', 'a']));
        $this->assertSame([], $field->sanitize('a'));
    }

    public function testSanitizeText(): void
    {
        $field = FieldDefinition::fromMetadata($this->createField('text_line'));

        $this->assertSame('foo', $field->sanitize('  foo '));
        $this->assertNull($field->sanitize('   '));
        $this->assertNull($field->sanitize(null));
        $this->assertNull($field->sanitize(['x']));
    }

    public function testSanitizeNumber(): void
    {
        $field = FieldDefinition::fromMetadata($this->createField('number', '5'));

        $this->assertSame(10, $field->sanitize('10'));
        $this->assertSame(1.5, $field->sanitize('1.5'));
        $this->assertSame(7, $field->sanitize(7));
        $this->assertSame(5, $field->sanitize('abc'));
    }

    public function testSanitizeRawKeepsJsonSerializableValues(): void
    {
        $field = FieldDefinition::fromMetadata($this->createField('media_selection'));

        $this->assertSame([['id' => 1]], $field->sanitize([['id' => 1]]));
        $this->assertSame('x', $field->sanitize('x'));
        $this->assertNull($field->sanitize(null));
        $this->assertNull($field->sanitize(new \stdClass()));
        $this->assertSame([1], \array_values($field->sanitize([1, new \stdClass()])));
    }
}
