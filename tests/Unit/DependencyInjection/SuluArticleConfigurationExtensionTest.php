<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\DependencyInjection;

use Manuxi\SuluArticleConfigurationBundle\Admin\FormMetadata\ArticleConfigurationFormMetadataLoader;
use Manuxi\SuluArticleConfigurationBundle\Command\MigrateToJsonCommand;
use Manuxi\SuluArticleConfigurationBundle\DependencyInjection\SuluArticleConfigurationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class SuluArticleConfigurationExtensionTest extends TestCase
{
    private string $projectDirectory;

    protected function setUp(): void
    {
        $this->projectDirectory = \sys_get_temp_dir() . '/article_configuration_ext_' . \bin2hex(\random_bytes(4));
        \mkdir($this->projectDirectory . '/config', 0777, true);
    }

    protected function tearDown(): void
    {
        @\rmdir($this->projectDirectory . '/config/article_configuration');
        @\rmdir($this->projectDirectory . '/config');
        @\rmdir($this->projectDirectory);
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->projectDirectory);
        foreach (['sulu_admin', 'framework'] as $alias) {
            $container->registerExtension(new class($alias) extends Extension {
                public function __construct(private string $alias)
                {
                }

                public function load(array $configs, ContainerBuilder $container): void
                {
                }

                public function getAlias(): string
                {
                    return $this->alias;
                }
            });
        }

        return $container;
    }

    public function testLoadRegistersServices(): void
    {
        $container = new ContainerBuilder();
        (new SuluArticleConfigurationExtension())->load([], $container);

        $loader = $container->getDefinition(ArticleConfigurationFormMetadataLoader::class);
        $this->assertTrue($loader->hasTag('sulu_admin.form_metadata_loader'));
        $this->assertTrue($container->getDefinition(MigrateToJsonCommand::class)->hasTag('console.command'));
    }

    public function testAlias(): void
    {
        $this->assertSame('sulu_article_configuration', (new SuluArticleConfigurationExtension())->getAlias());
    }

    public function testPrependRegistersBundleFormsAndResource(): void
    {
        $container = $this->createContainer();

        (new SuluArticleConfigurationExtension())->prepend($container);

        $config = $container->getExtensionConfig('sulu_admin')[0];
        $this->assertCount(1, $config['forms']['directories']);
        $this->assertStringEndsWith('/Resources/config/forms', $config['forms']['directories'][0]);
        $this->assertArrayHasKey('article_configurations', $config['resources']);
    }

    public function testPrependRegistersProjectDirectoryWhenItExists(): void
    {
        \mkdir($this->projectDirectory . '/config/article_configuration');
        $container = $this->createContainer();

        (new SuluArticleConfigurationExtension())->prepend($container);

        $directories = $container->getExtensionConfig('sulu_admin')[0]['forms']['directories'];
        $this->assertCount(2, $directories);
        $this->assertSame($this->projectDirectory . '/config/article_configuration', $directories[1]);
    }
}
