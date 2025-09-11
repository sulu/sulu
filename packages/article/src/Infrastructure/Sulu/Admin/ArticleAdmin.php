<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Infrastructure\Sulu\Admin;

use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\View\ActivityViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Content\Infrastructure\Sulu\Admin\ContentViewBuilderFactoryInterface;

/**
 * @final
 *
 * @internal This class is internal and should not be extended or overwritten.
 *           You can create a separate admin class in your project and get the
 *           respective object from the collection to extend a navigation item or a view
 *
 * @experimental
 */
class ArticleAdmin extends Admin
{
    public const SECURITY_CONTEXT = 'sulu.article.articles';

    public const LIST_VIEW = 'sulu_article.article.list';

    public const ADD_TABS_VIEW = 'sulu_article.article.add_tabs';

    public const EDIT_TABS_VIEW = 'sulu_article.article.edit_tabs';

    public function __construct(
        private ViewBuilderFactoryInterface $viewBuilderFactory,
        private ContentViewBuilderFactoryInterface $contentViewBuilderFactory,
        private SecurityCheckerInterface $securityChecker,
        private LocalizationManagerInterface $localizationManager,
        private ActivityViewBuilderFactoryInterface $activityViewBuilderFactory,
        private GroupProviderInterface $groupProvider,
    ) {
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if (!$this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            return;
        }

        $hasArticleTypeWithEditPermissions = false;
        foreach ($this->groupProvider->getGroups() as $group) {
            $securityContext = static::getArticleSecurityContext($group->identifier);
            if (!$this->securityChecker->hasPermission($securityContext, PermissionTypes::EDIT)) {
                continue;
            }

            $hasArticleTypeWithEditPermissions = true;

            break;
        }

        if (!$hasArticleTypeWithEditPermissions) {
            return;
        }

        $navigationItem = new NavigationItem('sulu_article.articles');
        $navigationItem->setPosition(20);
        $navigationItem->setIcon('su-newspaper');
        $navigationItem->setView(static::LIST_VIEW);

        $navigationItemCollection->add($navigationItem);
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        if (!$this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            return;
        }

        $locales = $this->localizationManager->getLocales();
        $resourceKey = ArticleInterface::RESOURCE_KEY;

        $viewCollection->add(
            $this->viewBuilderFactory->createTabViewBuilder(static::LIST_VIEW, '/articles')
                ->addRouterAttributesToBlacklist(['active', 'filter', 'limit', 'page', 'search', 'sortColumn', 'sortOrder']),
        );

        foreach ($this->groupProvider->getGroups() as $group) {
            $this->configureGroupViews($group, $locales, $resourceKey, $viewCollection);
        }
    }

    private function hasPermission(string $groupIdentifier, string $permission): bool
    {
        return $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, $permission)
            && $this->securityChecker->hasPermission(static::getArticleSecurityContext($groupIdentifier), $permission);
    }

    /**
     * @param string[] $locales
     */
    private function configureGroupViews(FormGroup $group, array $locales, string $resourceKey, ViewCollection $viewCollection): void
    {
        if (!$this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            return;
        }

        $groupIdentifier = $group->identifier;

        $listToolbarActions = [];

        if ($this->hasPermission($groupIdentifier, PermissionTypes::ADD)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.add');
        }

        if ($this->hasPermission($groupIdentifier, PermissionTypes::DELETE)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.delete');
        }

        if ($this->hasPermission($groupIdentifier, PermissionTypes::VIEW)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.export');
        }

        $viewCollection->add(
            $this->viewBuilderFactory->createListViewBuilder(static::LIST_VIEW . '_' . $groupIdentifier, '/:locale/' . $groupIdentifier)
                ->setResourceKey($resourceKey)
                ->setListKey($resourceKey)
                ->setTitle($group->title)
                ->addListAdapters(['table'])
                ->setTabTitle($group->title)
                ->addLocales($locales)
                ->addRequestParameters(['templates' => \implode(',', $group->templates)])
                ->setDefaultLocale($locales[0] ?? '')
                ->setAddView(static::ADD_TABS_VIEW . '_' . $groupIdentifier)
                ->setEditView(static::EDIT_TABS_VIEW . '_' . $groupIdentifier)
                ->addToolbarActions($listToolbarActions)
                ->setParent(static::LIST_VIEW),
        );
        $viewCollection->add(
            $this->viewBuilderFactory->createResourceTabViewBuilder(static::ADD_TABS_VIEW . '_' . $groupIdentifier, '/:locale/' . $groupIdentifier . '/add')
                ->setResourceKey($resourceKey)
                ->addLocales($locales)
                ->setBackView(static::LIST_VIEW),
        );
        $viewCollection->add(
            $this->viewBuilderFactory->createResourceTabViewBuilder(static::EDIT_TABS_VIEW . '_' . $groupIdentifier, '/:locale/' . $groupIdentifier . '/:id') // TODO should be uuid
                ->setResourceKey($resourceKey)
                ->addLocales($locales)
                ->setBackView(static::LIST_VIEW)
                ->setTitleProperty('name'),
        );

        $viewBuilders = $this->contentViewBuilderFactory->createViews(
            ArticleInterface::class,
            static::EDIT_TABS_VIEW . '_' . $groupIdentifier,
            static::ADD_TABS_VIEW . '_' . $groupIdentifier,
            $this->getArticleSecurityContext($groupIdentifier),
        );

        foreach ($viewBuilders as $viewBuilder) {
            $viewCollection->add($viewBuilder);
        }

        $editView = $viewCollection->get(static::EDIT_TABS_VIEW . '_' . $groupIdentifier . '.content');
        if (\method_exists($editView, 'addMetadataRequestParameters')) {
            $editView->addMetadataRequestParameters([
                'templates' => \implode(',', $group->templates),
            ]);
        }

        $addView = $viewCollection->get(static::ADD_TABS_VIEW . '_' . $groupIdentifier . '.content');
        if (\method_exists($addView, 'addMetadataRequestParameters')) {
            $addView->addMetadataRequestParameters([
                'templates' => \implode(',', $group->templates),
            ]);
        }

        $insightsResourceTabViewName = self::EDIT_TABS_VIEW . '_' . $groupIdentifier . '.insights';
        if ($viewCollection->has($insightsResourceTabViewName) && $this->activityViewBuilderFactory->hasActivityListPermission()) {
            $viewCollection->add(
                $this->activityViewBuilderFactory
                    ->createActivityListViewBuilder(
                        $insightsResourceTabViewName . '.activity',
                        '/activities',
                        ArticleInterface::RESOURCE_KEY,
                    )
                    ->setParent($insightsResourceTabViewName),
            );
        }
    }

    public function getSecurityContexts()
    {
        $securityContext = [];

        foreach ($this->groupProvider->getGroups() as $group) {
            $securityContext[static::getArticleSecurityContext($group->identifier)] = [
                PermissionTypes::VIEW,
                PermissionTypes::ADD,
                PermissionTypes::EDIT,
                PermissionTypes::DELETE,
                PermissionTypes::LIVE,
            ];
        }

        return [
            'Sulu' => [
                'Article' => \array_merge(
                    [
                        static::SECURITY_CONTEXT => [
                            PermissionTypes::VIEW,
                            PermissionTypes::ADD,
                            PermissionTypes::EDIT,
                            PermissionTypes::DELETE,
                            PermissionTypes::LIVE,
                        ],
                    ],
                    $securityContext,
                ),
            ],
        ];
    }

    public static function getArticleSecurityContext(string $groupIdentifier): string
    {
        return \sprintf('%s_%s', static::SECURITY_CONTEXT, $groupIdentifier);
    }
}
