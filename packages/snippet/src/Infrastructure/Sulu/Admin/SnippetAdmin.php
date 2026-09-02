<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Infrastructure\Sulu\Admin;

use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\View\ActivityViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\DropdownToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Content\Infrastructure\Sulu\Admin\ContentViewBuilderFactoryInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;

/**
 * @final
 *
 * @internal This class is internal and should not be extended or overwritten.
 *           You can create a separate admin class in your project and get the
 *           respective object from the collection to extend a navigation item or a view
 */
class SnippetAdmin extends Admin
{
    public const SECURITY_CONTEXT = 'sulu.snippet.snippets';

    public const LIST_VIEW = 'sulu_snippet.snippet.list';

    public const ADD_TABS_VIEW = 'sulu_snippet.snippet.add_tabs';

    public const EDIT_TABS_VIEW = 'sulu_snippet.snippet.edit_tabs';

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
        $groups = $this->groupProvider->getGroups(SnippetInterface::TEMPLATE_TYPE);

        $hasGroupWithEditPermission = false;
        foreach ($groups as $group) {
            if ($this->securityChecker->hasPermission($this->resolveSecurityContext($groups, $group), PermissionTypes::EDIT)) {
                $hasGroupWithEditPermission = true;
                break;
            }
        }

        if (!$hasGroupWithEditPermission) {
            return;
        }

        $navigationItem = new NavigationItem('sulu_snippet.snippets');
        $navigationItem->setPosition(15);
        $navigationItem->setIcon('su-snippet');
        $navigationItem->setView(static::LIST_VIEW);

        $navigationItemCollection->add($navigationItem);
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $locales = $this->localizationManager->getLocales();
        $resourceKey = SnippetInterface::RESOURCE_KEY;

        $viewCollection->add(
            $this->viewBuilderFactory->createTabViewBuilder(static::LIST_VIEW, '/' . $resourceKey)
                ->addRouterAttributesToBlacklist(['active', 'filter', 'limit', 'page', 'search', 'sortColumn', 'sortOrder']),
        );

        $groups = $this->groupProvider->getGroups(SnippetInterface::TEMPLATE_TYPE);
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

        return static::getSnippetSecurityContext($group->identifier);
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
        $templateKeys = \implode(',', $group->templates);

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
                ->addRequestParameters(['templateKeys' => $templateKeys])
                ->addMetadataRequestParameters(['templates' => $templateKeys])
                ->setDefaultLocale($locales[0] ?? '')
                ->setAddView(static::ADD_TABS_VIEW . '_' . $groupIdentifier)
                ->setEditView(static::EDIT_TABS_VIEW . '_' . $groupIdentifier)
                ->addToolbarActions($listToolbarActions)
                ->setParent(static::LIST_VIEW),
        );
        $viewCollection->add(
            $this->viewBuilderFactory->createResourceTabViewBuilder(static::ADD_TABS_VIEW . '_' . $groupIdentifier, '/' . $resourceKey . '/:locale/' . $groupIdentifier . '/add')
                ->setResourceKey($resourceKey)
                ->addLocales($locales)
                ->setBackView(static::LIST_VIEW . '_' . $groupIdentifier),
        );
        $viewCollection->add(
            $this->viewBuilderFactory->createResourceTabViewBuilder(static::EDIT_TABS_VIEW . '_' . $groupIdentifier, '/' . $resourceKey . '/:locale/' . $groupIdentifier . '/:id') // TODO should be uuid
                ->setResourceKey($resourceKey)
                ->addLocales($locales)
                ->setBackView(static::LIST_VIEW . '_' . $groupIdentifier)
                ->setTitleProperty('title'),
        );

        $formToolbarActions = $this->contentViewBuilderFactory->getDefaultToolbarActions(SnippetInterface::class);
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
            SnippetInterface::class,
            static::EDIT_TABS_VIEW . '_' . $groupIdentifier,
            static::ADD_TABS_VIEW . '_' . $groupIdentifier,
            $securityContext,
            toolbarActions: $formToolbarActions,
        );

        if (0 === \count($viewBuilders)) {
            return;
        }

        foreach ($viewBuilders as $viewBuilder) {
            $viewCollection->add($viewBuilder);
        }

        $this->addMetadataRequestParameters(
            $viewCollection,
            static::EDIT_TABS_VIEW . '_' . $groupIdentifier . '.content',
            ['templates' => $templateKeys]
        );

        $this->addMetadataRequestParameters(
            $viewCollection,
            static::ADD_TABS_VIEW . '_' . $groupIdentifier . '.content',
            ['templates' => $templateKeys]
        );

        $insightsResourceTabViewName = static::EDIT_TABS_VIEW . '_' . $groupIdentifier . '.insights';
        if ($viewCollection->has($insightsResourceTabViewName) && $this->activityViewBuilderFactory->hasActivityListPermission()) {
            $viewCollection->add(
                $this->activityViewBuilderFactory
                    ->createActivityListViewBuilder(
                        $insightsResourceTabViewName . '.activity',
                        '/activities',
                        SnippetInterface::RESOURCE_KEY,
                    )
                    ->setParent($insightsResourceTabViewName),
            );
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function addMetadataRequestParameters(ViewCollection $viewCollection, string $viewName, array $metadata): void
    {
        if (!$viewCollection->has($viewName)) {
            return;
        }

        $view = $viewCollection->get($viewName);
        if (\method_exists($view, 'addMetadataRequestParameters')) {
            $view->addMetadataRequestParameters($metadata);
        }
    }

    /**
     * @return mixed[]
     */
    public function getSecurityContexts()
    {
        $groupSecurityContexts = [];
        $groups = $this->groupProvider->getGroups(SnippetInterface::TEMPLATE_TYPE);
        if (1 !== \count($groups)) {
            foreach ($groups as $group) {
                if (GroupProviderInterface::DEFAULT_GROUP === $group->identifier) {
                    continue;
                }

                $groupSecurityContexts[static::getSnippetSecurityContext($group->identifier)] = [
                    PermissionTypes::VIEW,
                    PermissionTypes::ADD,
                    PermissionTypes::EDIT,
                    PermissionTypes::DELETE,
                    PermissionTypes::LIVE,
                ];
            }
        }

        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'Snippet' => \array_merge(
                    [
                        static::SECURITY_CONTEXT => [
                            PermissionTypes::VIEW,
                            PermissionTypes::ADD,
                            PermissionTypes::EDIT,
                            PermissionTypes::DELETE,
                            PermissionTypes::LIVE,
                        ],
                    ],
                    $groupSecurityContexts,
                ),
            ],
        ];
    }

    public static function getSnippetSecurityContext(string $groupIdentifier): string
    {
        return \sprintf('%s_%s', static::SECURITY_CONTEXT, $groupIdentifier);
    }
}
