<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Admin;

use Manuxi\SuluArticleConfigurationBundle\Admin\ArticleConfigurationAdmin;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\View\FormViewBuilderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ArticleConfigurationAdminTest extends TestCase
{
    private $viewBuilderFactory;
    private $groupProvider;
    private $securityChecker;
    private $admin;
    private $viewCollection;

    protected function setUp(): void
    {
        $this->viewBuilderFactory = $this->createMock(ViewBuilderFactoryInterface::class);
        $this->groupProvider = $this->createMock(GroupProviderInterface::class);
        $this->securityChecker = $this->createMock(SecurityCheckerInterface::class);
        $this->viewCollection = $this->createMock(ViewCollection::class);

        $this->admin = new ArticleConfigurationAdmin(
            $this->viewBuilderFactory,
            $this->groupProvider,
            $this->securityChecker
        );
    }

    public function testConfigureViews(): void
    {
        $group = new \stdClass();
        $group->identifier = 'default';
        $group->title = 'Default';

        $this->groupProvider->method('getGroups')->willReturn([$group]);

        $formViewBuilder = $this->createMock(FormViewBuilderInterface::class);
        $this->viewBuilderFactory->method('createFormViewBuilder')->willReturn($formViewBuilder);

        $formViewBuilder->method('setResourceKey')->willReturnSelf();
        $formViewBuilder->method('setFormKey')->willReturnSelf();
        $formViewBuilder->method('setTabTitle')->willReturnSelf();
        $formViewBuilder->method('setTabOrder')->willReturnSelf();
        $formViewBuilder->method('setParent')->willReturnSelf();
        $formViewBuilder->method('addToolbarActions')->willReturnSelf();

        $this->viewCollection->method('has')->willReturn(true);
        $this->viewCollection->expects($this->exactly(2))->method('add');

        $this->admin->configureViews($this->viewCollection);
    }

    public function testConfigureViewsSkipsWhenParentViewNotFound(): void
    {
        $group = new \stdClass();
        $group->identifier = 'default';
        $group->title = 'Default';

        $this->groupProvider->method('getGroups')->willReturn([$group]);
        $this->viewCollection->method('has')->willReturn(false);
        $this->viewCollection->expects($this->never())->method('add');

        $this->admin->configureViews($this->viewCollection);
    }

    public function testGetConfigKey(): void
    {
        $this->assertEquals('article_configuration', $this->admin->getConfigKey());
    }
}