<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SuluArticleConfigurationExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'resources' => [
                        'article_configurations' => [
                            'routes' => [
                                'detail' => 'app.get_article_configurations',
                            ],
                        ],
                    ],
                ]
            );
        }

        $container->loadFromExtension('framework', [
            'default_locale' => 'en',
            'translator' => ['paths' => [__DIR__ . '/../Resources/translations/']],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('sulu_article_configuration.schema', $config);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }
}