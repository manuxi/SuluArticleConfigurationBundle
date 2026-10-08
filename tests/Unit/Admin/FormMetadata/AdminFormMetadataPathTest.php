<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Admin\FormMetadata;

use Manuxi\SuluArticleConfigurationBundle\Admin\FormMetadata\ArticleConfigurationFormMetadataLoader;
use Manuxi\SuluArticleConfigurationBundle\Schema\MetadataReader;
use Manuxi\SuluArticleConfigurationBundle\Tests\Support\TestSchemaFactory;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataProvider;

/**
 * Runs the request path of the admin (/admin/metadata/form/<key>) through Sulu's FormMetadataProvider with the
 * loaders in container order, while Sulu's XML loader knows the raw template file under the same key.
 */
class AdminFormMetadataPathTest extends TestCase
{
    public function testAdminReceivesTheComposedFormForATemplateWithItsOwnFile(): void
    {
        $xmlLoader = TestSchemaFactory::createXmlLoader();
        $bundleLoader = new ArticleConfigurationFormMetadataLoader(TestSchemaFactory::createComposer($xmlLoader));

        $this->assertNotNull(
            $xmlLoader->getMetadata('article_configuration_template_blog_post', 'en', []),
            'precondition: the raw template file is known to the XML loader under the same key'
        );

        $provider = new FormMetadataProvider([$bundleLoader, $xmlLoader], [], [], 'en');
        $form = $provider->getMetadata(ArticleConfigurationFormMetadataLoader::getFormKey(TestSchemaFactory::BLOG_TEMPLATE), 'en');

        $this->assertInstanceOf(FormMetadata::class, $form);
        $fields = MetadataReader::flattenFields($form->getItems());

        $this->assertArrayHasKey('layoutStyle', $fields, 'from the template level');
        $this->assertArrayHasKey('readingSpeed', $fields, 'from the template level');
        $this->assertArrayHasKey('showSummary', $fields, 'from the group level');
        $this->assertArrayHasKey('heroVariant', $fields, 'from the group level');
        $this->assertArrayHasKey('enableSidebar', $fields, 'from the base level');
        $this->assertArrayHasKey('default', $fields, 'from the base level');
        $this->assertArrayNotHasKey('showToc', $fields, 'removed by the template level');
        $this->assertArrayHasKey('template_default', $form->getItems());
        $this->assertArrayHasKey('hero', $form->getItems());
    }
}
