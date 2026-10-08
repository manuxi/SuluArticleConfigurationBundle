<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Schema;

use Manuxi\SuluArticleConfigurationBundle\Schema\MetadataReader;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;

class MetadataReaderTest extends TestCase
{
    private function createTag(string $name): TagMetadata
    {
        $tag = new TagMetadata();
        $tag->setName($name);

        return $tag;
    }

    public function testHasTag(): void
    {
        $field = new FieldMetadata('a');
        $this->assertFalse(MetadataReader::hasTag($field, 'x'));

        $field->addTag($this->createTag('other'));
        $field->addTag($this->createTag('x'));

        $this->assertTrue(MetadataReader::hasTag($field, 'x'));
        $this->assertTrue(MetadataReader::hasTag($field, 'other'));
        $this->assertFalse(MetadataReader::hasTag($field, 'missing'));
    }

    public function testFindOption(): void
    {
        $field = new FieldMetadata('a');
        $this->assertNull(MetadataReader::findOption($field, 'default_value'));

        $option = new OptionMetadata();
        $option->setName('default_value');
        $option->setValue('true');
        $field->addOption($option);

        $this->assertSame($option, MetadataReader::findOption($field, 'default_value'));
        $this->assertNull(MetadataReader::findOption($field, 'values'));
    }

    public function testFlattenFieldsWalksNestedSections(): void
    {
        $inner = new SectionMetadata('inner');
        $inner->addItem(new FieldMetadata('c'));

        $outer = new SectionMetadata('outer');
        $outer->addItem(new FieldMetadata('a'));
        $outer->addItem($inner);

        $fields = MetadataReader::flattenFields([$outer, new FieldMetadata('d')]);

        $this->assertSame(['a', 'c', 'd'], \array_keys($fields));
        $this->assertContainsOnlyInstancesOf(FieldMetadata::class, $fields);
    }

    public function testFlattenFieldsIgnoresUnknownItems(): void
    {
        $this->assertSame([], MetadataReader::flattenFields(['string', 5, null]));
        $this->assertSame([], MetadataReader::flattenFields([]));
    }
}
