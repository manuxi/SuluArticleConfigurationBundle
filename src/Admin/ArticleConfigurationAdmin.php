<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Admin;

use Manuxi\SuluArticleConfigurationBundle\Admin\FormMetadata\ArticleConfigurationFormMetadataLoader;
use Manuxi\SuluArticleConfigurationBundle\Service\ArticleGroupProvider;
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ArticleConfigurationAdmin extends Admin
{
    final public const ARTICLE_CONFIGURATION_RESOURCE_KEY = 'article_configurations';

    public function __construct(
        private ViewBuilderFactoryInterface $viewBuilderFactory,
        private ArticleGroupProvider $groupProvider,
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

            $toolbarActions = [];
            if ($this->hasPermission($groupIdentifier, PermissionTypes::EDIT, $securityContext !== ArticleAdmin::SECURITY_CONTEXT)) {
                $toolbarActions[] = new ToolbarAction('sulu_admin.save');
            }

            $addViewName = ArticleAdmin::ADD_TABS_VIEW . '_' . $groupIdentifier;

            foreach ($group->templates as $templateKey) {
                $this->addConfigurationView($viewCollection, $editViewName, $templateKey, $toolbarActions);

                if ($viewCollection->has($addViewName)) {
                    $this->addConfigurationView($viewCollection, $addViewName, $templateKey, $toolbarActions);
                }
            }
        }
    }

    /**
     * One tab per template; the tab condition shows only the tab matching the template of the article.
     *
     * @param ToolbarAction[] $toolbarActions
     */
    private function addConfigurationView(ViewCollection $viewCollection, string $parentViewName, string $templateKey, array $toolbarActions): void
    {
        $viewCollection->add(
            $this->viewBuilderFactory
                ->createFormViewBuilder($parentViewName . '.configuration_' . $templateKey, '/configuration/' . $templateKey)
                ->setResourceKey(self::ARTICLE_CONFIGURATION_RESOURCE_KEY)
                ->setFormKey(ArticleConfigurationFormMetadataLoader::getFormKey($templateKey))
                ->setTabTitle('sulu_article_configuration.title')
                ->setTabOrder(2048)
                ->setTabCondition(\sprintf("template == '%s'", \addcslashes($templateKey, "'\\")))
                ->setParent($parentViewName)
                ->addToolbarActions($toolbarActions)
        );
    }

    public function getConfigKey(): ?string
    {
        return 'article_configuration';
    }
}