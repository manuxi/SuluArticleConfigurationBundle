<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\DependencyInjection;

use Manuxi\SuluArticleConfigurationBundle\Admin\FormMetadata\ArticleConfigurationFormMetadataLoader;
use Manuxi\SuluArticleConfigurationBundle\DependencyInjection\SuluArticleConfigurationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Sulu's FormMetadataProvider uses the first loader that returns a form. The composed form must win over the raw
 * template file that Sulu's XML loader knows under the same key, in every Sulu 3.0 release.
 */
class FormMetadataLoaderOrderTest extends TestCase
{
    private const TAG = 'sulu_admin.form_metadata_loader';

    /**
     * @param array<string, array<string, int>> $suluLoaders service id => tag attributes, registered before the bundle
     *
     * @return list<string>
     */
    private function sortedLoaderIds(array $suluLoaders): array
    {
        $container = new ContainerBuilder();
        foreach ($suluLoaders as $id => $attributes) {
            $container->register($id, \stdClass::class)->addTag(self::TAG, $attributes);
        }

        (new SuluArticleConfigurationExtension())->load([], $container);

        $sorter = new class() {
            use PriorityTaggedServiceTrait;

            /**
             * @return Reference[]
             */
            public function sort(string $tag, ContainerBuilder $container): array
            {
                return $this->findAndSortTaggedServices($tag, $container);
            }
        };

        return \array_map('strval', $sorter->sort(self::TAG, $container));
    }

    public function testBundleLoaderRunsFirstWithSulu303Registration(): void
    {
        $ids = $this->sortedLoaderIds([
            'sulu_admin.xml_form_metadata_loader' => [],
            'sulu_admin.template_form_metadata_loader' => [],
        ]);

        $this->assertSame(ArticleConfigurationFormMetadataLoader::class, $ids[0]);
    }

    public function testBundleLoaderRunsFirstWithSulu3010Registration(): void
    {
        $ids = $this->sortedLoaderIds([
            'sulu_admin.xml_form_metadata_loader' => ['priority' => -64],
            'sulu_admin.template_form_metadata_loader' => ['priority' => 512],
        ]);

        $this->assertSame(ArticleConfigurationFormMetadataLoader::class, $ids[0]);
    }
}
