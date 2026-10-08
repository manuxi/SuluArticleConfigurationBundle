<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Support;

use Manuxi\SuluArticleConfigurationBundle\Admin\FormMetadata\ArticleConfigurationFormComposer;
use Manuxi\SuluArticleConfigurationBundle\Schema\ConfigurationSchema;
use Manuxi\SuluArticleConfigurationBundle\Service\ArticleGroupProvider;
use PHPUnit\Framework\MockObject\Generator;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataLoaderInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Loader\FormXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\MetaXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\PropertiesXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\SchemaXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\TagXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SchemaMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\ChainFieldMetadataValidator;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlFormMetadataLoader;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperRegistry;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Translation\Translator;

/**
 * Builds the real Sulu XML form loader over the base form of the bundle plus the fixtures of the tests.
 */
final class TestSchemaFactory
{
    public const BLOG_TEMPLATE = 'blog_post';

    private static ?string $cacheDirectory = null;

    public static function createXmlLoader(): XmlFormMetadataLoader
    {
        $tagParser = new TagXmlParser();
        $propertiesParser = new PropertiesXmlParser($tagParser, new MetaXmlParser(new Translator('en'), ['en' => 'en', 'de' => 'de']));
        $formLoader = new FormXmlLoader(
            $propertiesParser,
            new SchemaXmlParser(),
            $tagParser,
            self::createSchemaMetadataProvider()
        );

        $loader = new XmlFormMetadataLoader(
            $formLoader,
            new ChainFieldMetadataValidator([]),
            [
                \dirname(__DIR__, 2) . '/src/Resources/config/forms',
                \dirname(__DIR__) . '/Fixtures/forms',
            ],
            self::getCacheDirectory(),
            true
        );

        /* Sulu 3.0.0 only reads forms that the cache warmer has written, later releases warm up on demand. */
        $loader->warmUp(self::getCacheDirectory());

        return $loader;
    }

    public static function createSchemaMetadataProvider(): SchemaMetadataProvider
    {
        return new SchemaMetadataProvider(new PropertyMetadataMapperRegistry(new ServiceLocator([])));
    }

    public static function createComposer(?FormMetadataLoaderInterface $loader = null): ArticleConfigurationFormComposer
    {
        $generator = new Generator();
        $groupProvider = $generator->getMock(ArticleGroupProvider::class, ['getGroupIdentifier'], [], '', false);
        $groupProvider->method('getGroupIdentifier')->willReturnCallback(
            static fn (string $templateKey): string => self::BLOG_TEMPLATE === $templateKey ? 'blog' : 'default'
        );

        return new ArticleConfigurationFormComposer($loader ?? self::createXmlLoader(), $groupProvider, self::createSchemaMetadataProvider());
    }

    public static function createSchema(): ConfigurationSchema
    {
        return new ConfigurationSchema(self::createComposer());
    }

    private static function getCacheDirectory(): string
    {
        if (null === self::$cacheDirectory) {
            self::$cacheDirectory = \sys_get_temp_dir() . '/article_configuration_test_' . \bin2hex(\random_bytes(4));
            \mkdir(self::$cacheDirectory, 0777, true);
            \register_shutdown_function(static function (): void {
                foreach (\glob(self::$cacheDirectory . '/*') ?: [] as $file) {
                    @\unlink($file);
                }
                @\rmdir(self::$cacheDirectory);
            });
        }

        return self::$cacheDirectory;
    }
}
