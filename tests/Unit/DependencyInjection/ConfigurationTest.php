<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\DependencyInjection;

use Manuxi\SuluArticleConfigurationBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }

    public function testEmptyConfiguration(): void
    {
        $result = $this->process([]);

        $this->assertSame([], $result['default']['fields'] ?? []);
        $this->assertSame([], $result['groups']);
        $this->assertSame([], $result['templates']);
    }

    public function testKeepsFieldNamesAndGroupNamesUntouched(): void
    {
        $result = $this->process([
            'groups' => ['my-group' => ['fields' => ['heroVariant' => ['type' => 'toggle']]]],
            'templates' => ['blog_post' => ['fields' => ['showToc' => false]]],
        ]);

        $this->assertArrayHasKey('my-group', $result['groups']);
        $this->assertArrayHasKey('heroVariant', $result['groups']['my-group']['fields']);
        $this->assertFalse($result['templates']['blog_post']['fields']['showToc']);
    }

    public function testRejectsUnknownFieldOption(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/Unknown field option/');

        $this->process(['default' => ['fields' => ['x' => ['type' => 'toggle', 'bogus' => 1]]]]);
    }

    public function testRejectsUnknownFieldType(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/Invalid field type/');

        $this->process(['default' => ['fields' => ['x' => ['type' => 'color']]]]);
    }

    public function testRejectsTrueAsFieldDefinition(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['default' => ['fields' => ['x' => true]]]);
    }
}
