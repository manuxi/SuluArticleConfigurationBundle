<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Admin;

use Sulu\Article\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ArticleConfigurationAdmin extends Admin
{
    final public const ARTICLE_CONFIGURATION_FORM_KEY = 'article_configuration';
    final public const ARTICLE_CONFIGURATION_RESOURCE_KEY = 'article_configurations';

    public function __construct(
        private ViewBuilderFactoryInterface $viewBuilderFactory,
        private GroupProviderInterface $groupProvider,
        private SecurityCheckerInterface $securityChecker,
    ) {
    }

    private function hasPermission(string $groupIdentifier, string $permission, bool $checkGroup): bool
    {
        return $this->securityChecker->hasPermission(ArticleAdmin::SECURITY_CONTEXT, $permission)
            && (false === $checkGroup || $this->securityChecker->hasPermission(ArticleAdmin::getArticleSecurityContext($groupIdentifier), $permission));
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $groups = $this->groupProvider->getGroups();

        foreach ($groups as $group) {
            $securityContext = ArticleAdmin::getArticleSecurityContext($group->identifier);
            if (1 === \count($groups)) {
                $securityContext = ArticleAdmin::SECURITY_CONTEXT;
            }

            $groupIdentifier = $group->identifier;
            $editViewName = ArticleAdmin::EDIT_TABS_VIEW . '_' . $groupIdentifier;

            if (!$viewCollection->has($editViewName)) {
                continue;
            }

            $listToolbarActions = [];
            if ($this->hasPermission($groupIdentifier, PermissionTypes::EDIT, $securityContext !== ArticleAdmin::SECURITY_CONTEXT)) {
                $listToolbarActions[] = new ToolbarAction('sulu_admin.save');
            }

            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder($editViewName . '.configuration', '/configuration')
                    ->setResourceKey(self::ARTICLE_CONFIGURATION_RESOURCE_KEY)
                    ->setFormKey(self::ARTICLE_CONFIGURATION_FORM_KEY)
                    ->setTabTitle('sulu_article_configuration.title')
                    ->setTabOrder(2048)
                    ->setParent($editViewName)
                    ->addToolbarActions($listToolbarActions)
            );

            $addViewName = ArticleAdmin::ADD_TABS_VIEW . '_' . $groupIdentifier;
            if ($viewCollection->has($addViewName)) {
                $viewCollection->add(
                    $this->viewBuilderFactory
                        ->createFormViewBuilder($addViewName . '.configuration', '/configuration')
                        ->setResourceKey(self::ARTICLE_CONFIGURATION_RESOURCE_KEY)
                        ->setFormKey(self::ARTICLE_CONFIGURATION_FORM_KEY)
                        ->setTabTitle('sulu_article_configuration.title')
                        ->setTabOrder(2048)
                        ->setParent($addViewName)
                        ->addToolbarActions($listToolbarActions)
                );
            }
        }
    }

    public function getConfigKey(): ?string
    {
        return 'article_configuration';
    }
}