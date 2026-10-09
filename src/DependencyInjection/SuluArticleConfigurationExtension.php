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
            $formDirectories = [__DIR__ . '/../Resources/config/forms'];

            $projectDirectory = $container->hasParameter('kernel.project_dir')
                ? $container->getParameter('kernel.project_dir')
                : null;
            if (\is_string($projectDirectory) && \is_dir($projectDirectory . '/config/article_configuration')) {
                $formDirectories[] = $projectDirectory . '/config/article_configuration';
            }

            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'forms' => [
                        'directories' => $formDirectories,
                    ],
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
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }
}