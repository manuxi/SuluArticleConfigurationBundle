<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\DependencyInjection;

use Manuxi\SuluArticleConfigurationBundle\Admin\FormMetadata\ArticleConfigurationFormMetadataLoader;
use Manuxi\SuluArticleConfigurationBundle\Command\MigrateToJsonCommand;
use Manuxi\SuluArticleConfigurationBundle\DependencyInjection\SuluArticleConfigurationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SuluArticleConfigurationExtensionTest extends TestCase
{
    public function testLoadRegistersSchemaParameterAndServices(): void
    {
        $container = new ContainerBuilder();
        (new SuluArticleConfigurationExtension())->load([
            ['templates' => ['blog_post' => ['fields' => ['showToc' => false]]]],
        ], $container);

        $schema = $container->getParameter('sulu_article_configuration.schema');
        $this->assertFalse($schema['templates']['blog_post']['fields']['showToc']);

        $loader = $container->getDefinition(ArticleConfigurationFormMetadataLoader::class);
        $this->assertTrue($loader->hasTag('sulu_admin.form_metadata_loader'));
        $this->assertTrue($container->getDefinition(MigrateToJsonCommand::class)->hasTag('console.command'));
    }

    public function testAlias(): void
    {
        $this->assertSame('sulu_article_configuration', (new SuluArticleConfigurationExtension())->getAlias());
    }

    public function testPrependRegistersResourceOnly(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new class() extends \Symfony\Component\DependencyInjection\Extension\Extension {
            public function load(array $configs, ContainerBuilder $container): void
            {
            }

            public function getAlias(): string
            {
                return 'sulu_admin';
            }
        });
        $container->registerExtension(new class() extends \Symfony\Component\DependencyInjection\Extension\Extension {
            public function load(array $configs, ContainerBuilder $container): void
            {
            }

            public function getAlias(): string
            {
                return 'framework';
            }
        });

        (new SuluArticleConfigurationExtension())->prepend($container);

        $config = $container->getExtensionConfig('sulu_admin')[0];
        $this->assertArrayHasKey('article_configurations', $config['resources']);
        $this->assertArrayNotHasKey('forms', $config);
    }
}
