<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Admin;

use Manuxi\SuluArticleConfigurationBundle\Admin\ArticleConfigurationAdmin;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\View\FormViewBuilderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class ArticleConfigurationAdminTest extends TestCase
{
    private $viewBuilderFactory;
    private $localizationManager;
    private $groupProvider;
    private $securityChecker;
    private $admin;
    private $viewCollection;

    protected function setUp(): void
    {
        $this->viewBuilderFactory = $this->createMock(ViewBuilderFactoryInterface::class);
        $this->localizationManager = $this->createMock(LocalizationManagerInterface::class);
        $this->groupProvider = $this->createMock(GroupProviderInterface::class);
        $this->securityChecker = $this->createMock(SecurityCheckerInterface::class);
        $this->viewCollection = $this->createMock(ViewCollection::class);

        $this->admin = new ArticleConfigurationAdmin(
            $this->viewBuilderFactory,
            $this->localizationManager,
            $this->groupProvider,
            $this->securityChecker
        );
    }

    public function testConfigureViews(): void
    {
        $this->localizationManager->method('getLocales')->willReturn(['en', 'de']);

        // Mock Group object
        $group = new \stdClass();
        $group->identifier = 'default';

        $this->groupProvider->method('getGroups')->willReturn([$group]);

        $formViewBuilder = $this->createMock(FormViewBuilderInterface::class);
        $this->viewBuilderFactory->method('createFormViewBuilder')->willReturn($formViewBuilder);

        // Chain method calls on FormViewBuilder
        $formViewBuilder->method('setResourceKey')->willReturnSelf();
        $formViewBuilder->method('setFormKey')->willReturnSelf();
        $formViewBuilder->method('setTabTitle')->willReturnSelf();
        $formViewBuilder->method('setTabOrder')->willReturnSelf();
        $formViewBuilder->method('addLocales')->willReturnSelf();
        $formViewBuilder->method('setParent')->willReturnSelf();
        $formViewBuilder->method('addToolbarActions')->willReturnSelf();

        $this->viewCollection->expects($this->exactly(2))->method('add');

        $this->admin->configureViews($this->viewCollection);
    }
}
