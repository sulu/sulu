<?php

declare(strict_types=1);

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
use Sulu\Bundle\AdminBundle\Admin\View\DropdownToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\PreviewFormViewBuilder;
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
        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);
        $hasArticleTypeWithEditPermissions = false;
        foreach ($groups as $group) {
            $securityContext = $this->resolveSecurityContext($groups, $group);
            if ($this->securityChecker->hasPermission($securityContext, PermissionTypes::EDIT)) {
                $hasArticleTypeWithEditPermissions = true;
                break;
            }
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
        $locales = $this->localizationManager->getLocales();
        $resourceKey = ArticleInterface::RESOURCE_KEY;

        $viewCollection->add(
            $this->viewBuilderFactory->createTabViewBuilder(static::LIST_VIEW, '/articles')
                ->addRouterAttributesToBlacklist(['active', 'filter', 'limit', 'page', 'search', 'sortColumn', 'sortOrder']),
        );

        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);
        foreach ($groups as $group) {
            $securityContext = $this->resolveSecurityContext($groups, $group);
            $this->configureGroupViews($group, $locales, $resourceKey, $viewCollection, $securityContext);
        }
    }

    /**
     * @param array<string, FormGroup> $groups
     */
    private function resolveSecurityContext(array $groups, FormGroup $group): string
    {
        if (1 === \count($groups) || GroupProviderInterface::DEFAULT_GROUP === $group->identifier) {
            return static::SECURITY_CONTEXT;
        }

        return static::getArticleSecurityContext($group->identifier);
    }

    /**
     * @param string[] $locales
     */
    private function configureGroupViews(FormGroup $group, array $locales, string $resourceKey, ViewCollection $viewCollection, string $securityContext): void
    {
        if (!$this->securityChecker->hasPermission($securityContext, PermissionTypes::EDIT)) {
            return;
        }

        $groupIdentifier = $group->identifier;

        $listToolbarActions = [];

        if ($this->securityChecker->hasPermission($securityContext, PermissionTypes::ADD)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.add');
        }

        if ($this->securityChecker->hasPermission($securityContext, PermissionTypes::DELETE)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.delete');
        }

        if ($this->securityChecker->hasPermission($securityContext, PermissionTypes::VIEW)) {
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
                ->addRequestParameters(['templateKeys' => \implode(',', $group->templates)])
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
                ->setBackView(static::LIST_VIEW . '_' . $groupIdentifier),
        );
        $viewCollection->add(
            $this->viewBuilderFactory->createResourceTabViewBuilder(static::EDIT_TABS_VIEW . '_' . $groupIdentifier, '/:locale/' . $groupIdentifier . '/:id') // TODO should be uuid
            ->setResourceKey($resourceKey)
                ->addLocales($locales)
                ->setBackView(static::LIST_VIEW . '_' . $groupIdentifier)
                ->setTitleProperty('title'),
        );

        $formToolbarActions = $this->contentViewBuilderFactory->getDefaultToolbarActions(ArticleInterface::class);
        $formToolbarActions['delete'] = new DropdownToolbarAction(
            'sulu_admin.delete',
            'su-trash-alt',
            [
                new ToolbarAction(
                    'sulu_admin.delete',
                    [
                        'visible_condition' => '(!_permissions || _permissions.delete)',
                    ]
                ),
                new ToolbarAction(
                    'sulu_admin.delete',
                    [
                        'visible_condition' => '(!_permissions || _permissions.delete)',
                        'delete_locale' => true,
                    ]
                ),
            ]
        );
        $formToolbarActions['edit'] = new DropdownToolbarAction(
            'sulu_admin.edit',
            'su-pen',
            [
                new ToolbarAction(
                    'sulu_admin.copy',
                    [
                        'visible_condition' => '(!_permissions || _permissions.edit)',
                    ]
                ),
                new ToolbarAction(
                    'sulu_admin.copy_locale',
                    [
                        'visible_condition' => '(!_permissions || _permissions.edit)',
                    ]
                ),
                new ToolbarAction(
                    'sulu_admin.delete_draft',
                    [
                        'visible_condition' => '(!_permissions || _permissions.live)',
                    ]
                ),
                new ToolbarAction(
                    'sulu_admin.set_unpublished',
                    [
                        'visible_condition' => '(!_permissions || _permissions.live)',
                    ]
                ),
            ]
        );

        $viewBuilders = $this->contentViewBuilderFactory->createViews(
            ArticleInterface::class,
            static::EDIT_TABS_VIEW . '_' . $groupIdentifier,
            static::ADD_TABS_VIEW . '_' . $groupIdentifier,
            $securityContext,
            toolbarActions: $formToolbarActions,
        );

        if (0 === \count($viewBuilders)) {
            return;
        }

        $previewCondition = 'shadowOn != true && availableLocales && locale in availableLocales';
        foreach ($viewBuilders as $viewBuilder) {
            if ($viewBuilder instanceof PreviewFormViewBuilder) {
                $viewBuilder->setPreviewCondition($previewCondition);
            }
            $viewCollection->add($viewBuilder);
        }

        $this->addRequestParameters(
            $viewCollection,
            static::EDIT_TABS_VIEW . '_' . $groupIdentifier . '.content',
            ['templates' => \implode(',', $group->templates)]
        );

        $this->addRequestParameters(
            $viewCollection,
            static::ADD_TABS_VIEW . '_' . $groupIdentifier . '.content',
            ['templates' => \implode(',', $group->templates)]
        );

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

    /**
     * @param array<string, mixed> $metadata
     */
    private function addRequestParameters(ViewCollection $viewCollection, string $viewName, array $metadata): void
    {
        if (!$viewCollection->has($viewName)) {
            return;
        }

        $view = $viewCollection->get($viewName);
        if (\method_exists($view, 'addMetadataRequestParameters')) {
            $view->addMetadataRequestParameters($metadata);
        }
    }

    public function getSecurityContexts()
    {
        $securityContext = [];
        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);
        if (1 !== \count($groups)) {
            foreach ($groups as $group) {
                if (GroupProviderInterface::DEFAULT_GROUP === $group->identifier) {
                    continue;
                }

                $securityContext[static::getArticleSecurityContext($group->identifier)] = [
                    PermissionTypes::VIEW,
                    PermissionTypes::ADD,
                    PermissionTypes::EDIT,
                    PermissionTypes::DELETE,
                    PermissionTypes::LIVE,
                    PermissionTypes::REVIEW,
                ];
            }
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
                            PermissionTypes::REVIEW,
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
