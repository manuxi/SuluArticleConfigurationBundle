<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Admin\FormMetadata;

use Manuxi\SuluArticleConfigurationBundle\Admin\FormMetadata\ArticleConfigurationFormComposer;
use Manuxi\SuluArticleConfigurationBundle\Admin\FormMetadata\ArticleConfigurationFormMetadataLoader;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;

class ArticleConfigurationFormMetadataLoaderTest extends TestCase
{
    public function testFormKey(): void
    {
        $this->assertSame(
            'article_configuration_template_blog_post',
            ArticleConfigurationFormMetadataLoader::getFormKey('blog_post')
        );
    }

    /**
     * @dataProvider foreignKeyProvider
     */
    public function testIgnoresForeignKeys(string $key): void
    {
        $composer = $this->createMock(ArticleConfigurationFormComposer::class);
        $composer->expects($this->never())->method('compose');

        $this->assertNull((new ArticleConfigurationFormMetadataLoader($composer))->getMetadata($key, 'en', []));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function foreignKeyProvider(): iterable
    {
        yield 'article' => ['article'];
        yield 'base form' => ['article_configuration'];
        yield 'group form' => ['article_configuration_group_blog'];
        yield 'prefix only' => ['article_configuration_template_'];
        yield 'other bundle' => ['contact_details'];
    }

    public function testDelegatesToTheComposerWithTemplateKeyAndLocale(): void
    {
        $form = new FormMetadata();
        $composer = $this->createMock(ArticleConfigurationFormComposer::class);
        $composer->expects($this->once())->method('compose')->with('blog_post', 'de')->willReturn($form);

        $result = (new ArticleConfigurationFormMetadataLoader($composer))->getMetadata('article_configuration_template_blog_post', 'de', []);

        $this->assertSame($form, $result);
    }
}
