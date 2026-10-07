<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Admin;

use Manuxi\SuluArticleConfigurationBundle\Admin\ArticleConfigurationAdmin;
use Manuxi\SuluArticleConfigurationBundle\Service\ArticleGroupProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\View\FormViewBuilderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ArticleConfigurationAdminTest extends TestCase
{
    private ViewBuilderFactoryInterface $viewBuilderFactory;
    private ArticleGroupProvider $groupProvider;
    private SecurityCheckerInterface $securityChecker;
    private ArticleConfigurationAdmin $admin;
    private ViewCollection $viewCollection;

    protected function setUp(): void
    {
        $this->viewBuilderFactory = $this->createMock(ViewBuilderFactoryInterface::class);
        $this->groupProvider = $this->createMock(ArticleGroupProvider::class);
        $this->securityChecker = $this->createMock(SecurityCheckerInterface::class);
        $this->viewCollection = $this->createMock(ViewCollection::class);

        $this->admin = new ArticleConfigurationAdmin(
            $this->viewBuilderFactory,
            $this->groupProvider,
            $this->securityChecker
        );
    }

    private function createFormViewBuilder(): FormViewBuilderInterface
    {
        $formViewBuilder = $this->createMock(FormViewBuilderInterface::class);
        foreach (['setResourceKey', 'setFormKey', 'setTabTitle', 'setTabOrder', 'setTabCondition', 'setParent', 'addToolbarActions'] as $method) {
            $formViewBuilder->method($method)->willReturnSelf();
        }

        return $formViewBuilder;
    }

    public function testConfigureViewsCreatesOneTabPerTemplateForEditAndAdd(): void
    {
        $this->groupProvider->method('getGroups')->willReturn([
            'default' => new FormGroup('default', 'Default', ['article_blog', 'article_news']),
        ]);

        $created = [];
        $formViewBuilder = $this->createFormViewBuilder();
        $this->viewBuilderFactory
            ->method('createFormViewBuilder')
            ->willReturnCallback(function (string $name, string $path) use (&$created, $formViewBuilder) {
                $created[$name] = $path;

                return $formViewBuilder;
            });

        $formViewBuilder->expects($this->exactly(4))->method('setFormKey')
            ->withConsecutive(
                ['article_configuration_article_blog'],
                ['article_configuration_article_blog'],
                ['article_configuration_article_news'],
                ['article_configuration_article_news']
            );
        $formViewBuilder->expects($this->exactly(4))->method('setTabCondition')
            ->withConsecutive(
                ["template == 'article_blog'"],
                ["template == 'article_blog'"],
                ["template == 'article_news'"],
                ["template == 'article_news'"]
            );

        $this->viewCollection->method('has')->willReturn(true);
        $this->viewCollection->expects($this->exactly(4))->method('add');

        $this->admin->configureViews($this->viewCollection);

        $this->assertCount(4, $created);
        $this->assertContains('/configuration/article_blog', $created);
        $this->assertContains('/configuration/article_news', $created);
    }

    public function testConfigureViewsSkipsAddViewWhenNotPresent(): void
    {
        $this->groupProvider->method('getGroups')->willReturn([
            'default' => new FormGroup('default', 'Default', ['article_blog']),
        ]);
        $this->viewBuilderFactory->method('createFormViewBuilder')->willReturn($this->createFormViewBuilder());

        $this->viewCollection->method('has')->willReturnCallback(
            static fn (string $name): bool => !\str_contains($name, 'add')
        );
        $this->viewCollection->expects($this->once())->method('add');

        $this->admin->configureViews($this->viewCollection);
    }

    public function testConfigureViewsEscapesQuotesInTemplateKey(): void
    {
        $this->groupProvider->method('getGroups')->willReturn([
            'default' => new FormGroup('default', 'Default', ["it's"]),
        ]);
        $formViewBuilder = $this->createFormViewBuilder();
        $this->viewBuilderFactory->method('createFormViewBuilder')->willReturn($formViewBuilder);
        $formViewBuilder->expects($this->atLeastOnce())->method('setTabCondition')->with("template == 'it\\'s'");

        $this->viewCollection->method('has')->willReturn(true);

        $this->admin->configureViews($this->viewCollection);
    }

    public function testConfigureViewsSkipsWhenParentViewNotFound(): void
    {
        $this->groupProvider->method('getGroups')->willReturn([
            'default' => new FormGroup('default', 'Default', ['article_blog']),
        ]);
        $this->viewCollection->method('has')->willReturn(false);
        $this->viewCollection->expects($this->never())->method('add');

        $this->admin->configureViews($this->viewCollection);
    }

    public function testGetConfigKey(): void
    {
        $this->assertEquals('article_configuration', $this->admin->getConfigKey());
    }
}
