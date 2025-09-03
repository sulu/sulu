<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Admin;

use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\View\ActivityViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\DropdownToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\PreviewFormViewBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\SaveWithFormDialogToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Infrastructure\Sulu\Admin\ContentViewBuilderFactoryInterface;
use Sulu\Page\Domain\Model\PageInterface;

/**
 * @final
 *
 * @internal This class is internal and should not be extended or overwritten.
 *           You can create a separate admin class in your project and get the
 *           respective object from the collection to extend a navigation item or a view
 *
 * @experimental
 */
class PageAdmin extends Admin
{
    /**
     * The prefix for the security context, the key of the webspace has to be appended.
     */
    public const SECURITY_CONTEXT_PREFIX = 'sulu.webspaces.';

    public const SECURITY_CONTEXT_GROUP = 'Webspaces';

    public const WEBSPACE_TABS_VIEW = 'sulu_page.webspaces';

    public const PAGES_VIEW = 'sulu_page.pages_list';

    public const ADD_FORM_VIEW = 'sulu_page.page_add_form';

    public const EDIT_FORM_VIEW = 'sulu_page.page_edit_form';

    public function __construct(
        private ViewBuilderFactoryInterface $viewBuilderFactory,
        private WebspaceManagerInterface $webspaceManager,
        private SecurityCheckerInterface $securityChecker,
        private ContentViewBuilderFactoryInterface $contentViewBuilderFactory,
        private ActivityViewBuilderFactoryInterface $activityViewBuilderFactory,
    ) {
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->getFirstWebspaceWithPermissions() instanceof Webspace) {
            $webspaceItem = new NavigationItem('sulu_page.webspaces');
            $webspaceItem->setPosition(10);
            $webspaceItem->setIcon('su-webspace');
            $webspaceItem->setView(static::WEBSPACE_TABS_VIEW);

            $navigationItemCollection->add($webspaceItem);
        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $firstWebspace = $this->getFirstWebspaceWithPermissions();

        $createPageSaveVisibleCondition = '!_permissions && (!__webspace || __webspace._permissions.edit)';
        $editPageSaveVisibleCondition = '_permissions && _permissions.edit';
        $saveVisibleCondition = '(' . $createPageSaveVisibleCondition . ') || (' . $editPageSaveVisibleCondition . ')';

        $createPagePublishVisibleCondition = '!_permissions  && (!__webspace || __webspace._permissions.live)';
        $editPagePublishVisibleCondition = '(!_permissions || _permissions.live)';
        $publishVisibleCondition = '(' . $createPagePublishVisibleCondition . ') || (' . $editPagePublishVisibleCondition . ')';

        $saveWithPublishingDropdown = new DropdownToolbarAction(
            'sulu_admin.save',
            'su-save',
            [
                new ToolbarAction(
                    'sulu_admin.save',
                    [
                        'label' => 'sulu_admin.save_draft',
                        'options' => ['action' => 'draft'],
                        'visible_condition' => $saveVisibleCondition,
                    ]
                ),
                new ToolbarAction(
                    'sulu_admin.save',
                    [
                        'label' => 'sulu_admin.save_publish',
                        'options' => ['action' => 'publish'],
                        'visible_condition' => '(' . $saveVisibleCondition . ') && (' . $publishVisibleCondition . ')',
                    ]
                ),
                new ToolbarAction(
                    'sulu_admin.publish',
                    [
                        'visible_condition' => $publishVisibleCondition,
                    ]
                ),
            ]
        );

        $formToolbarActionsWithType = [
            'save' => $saveWithPublishingDropdown,
            'type' => new ToolbarAction(
                'sulu_admin.type',
                [
                    'sort_by' => 'title',
                    'disabled_condition' => '(_permissions && !_permissions.edit)',
                ]
            ),
            'delete' => new DropdownToolbarAction(
                'sulu_admin.delete',
                'su-trash-alt',
                [
                    new ToolbarAction(
                        'sulu_admin.delete',
                        [
                            'visible_condition' => '(!_permissions || _permissions.delete) && url != "/"',
                            'router_attributes_to_back_view' => ['webspace'],
                        ]
                    ),
                    new ToolbarAction(
                        'sulu_admin.delete',
                        [
                            'visible_condition' => '(!_permissions || _permissions.delete) && url != "/"',
                            'router_attributes_to_back_view' => ['webspace'],
                            'delete_locale' => true,
                        ]
                    ),
                ]
            ),
            'edit' => new DropdownToolbarAction(
                'sulu_admin.edit',
                'su-pen',
                [
                    new ToolbarAction(
                        'sulu_admin.copy_locale',
                        [
                            'visible_condition' => '(!_permissions || _permissions.edit) && __webspace.localizations|length > 1',
                        ]
                    ),
                    new ToolbarAction(
                        'sulu_admin.delete_draft',
                        [
                            'visible_condition' => $publishVisibleCondition,
                        ]
                    ),
                    new ToolbarAction(
                        'sulu_admin.set_unpublished',
                        [
                            'visible_condition' => $publishVisibleCondition,
                        ]
                    ),
                ]
            ),
        ];

        $formToolbarActionsWithoutType = [
            $saveWithPublishingDropdown,
        ];

        $routerAttributesToFormRequest = ['parentId', 'webspace'];
        $routerAttributesToFormMetadata = ['webspace'];

        // This view has to be registered even if permissions for pages are missing
        // Otherwise the application breaks when other bundles try to add child views to this one
        $webspaceKey = '';
        if ($firstWebspace instanceof Webspace) {
            $webspaceKey = $firstWebspace->getKey();
        }
        $viewCollection->add(
            $this->viewBuilderFactory
                ->createViewBuilder(static::WEBSPACE_TABS_VIEW, '/webspaces/:webspace', 'sulu_page.webspace_tabs')
                ->setAttributeDefault('webspace', $webspaceKey)
        );

        if ($firstWebspace instanceof Webspace) {
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createViewBuilder(static::PAGES_VIEW, '/pages/:locale', 'sulu_page.page_list')
                    ->setAttributeDefault('locale', $firstWebspace->getDefaultLocalization()->getLocale())
                    ->setOption('tabTitle', 'sulu_page.pages')
                    ->setOption('tabOrder', 0)
                    ->setOption('tabPriority', 1024)
                    ->addRerenderAttribute('webspace')
                    ->setParent(static::WEBSPACE_TABS_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createViewBuilder(
                    static::ADD_FORM_VIEW,
                    '/webspaces/:webspace/pages/:locale/add/:parentId',
                    'sulu_page.page_tabs'
                )
                    ->setOption('backView', static::PAGES_VIEW)
                    ->setOption('routerAttributesToBackView', ['webspace'])
                    ->setOption('resourceKey', PageInterface::RESOURCE_KEY)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createViewBuilder(
                    static::EDIT_FORM_VIEW,
                    '/webspaces/:webspace/pages/:locale/:id',
                    'sulu_page.page_tabs'
                )
                    ->setOption('backView', static::PAGES_VIEW)
                    ->setOption('routerAttributesToBackView', ['id' => 'active', 'webspace'])
                    ->setOption('resourceKey', PageInterface::RESOURCE_KEY)
            );

            $viewBuilders = $this->contentViewBuilderFactory->createViews(
                contentRichEntityClass: PageInterface::class,
                editParentView: static::EDIT_FORM_VIEW,
                addParentView: static::ADD_FORM_VIEW,
                toolbarActions: $formToolbarActionsWithType
            );

            $previewCondition = 'shadowOn == false';
            $tabCondition = 'shadowOn == false';
            /** @var PreviewFormViewBuilder $viewBuilder */
            foreach ($viewBuilders as $viewBuilder) {
                if (PageAdmin::ADD_FORM_VIEW . '.content' === $viewBuilder->getName()) {
                    $viewBuilder
                        ->addRouterAttributesToEditView(['webspace'])
                        ->addRouterAttributesToFormRequest($routerAttributesToFormRequest)
                        ->addRouterAttributesToFormMetadata($routerAttributesToFormMetadata);
                }

                if (PageAdmin::EDIT_FORM_VIEW . '.content' === $viewBuilder->getName()) {
                    $viewBuilder
                        ->disablePreviewWebspaceChooser()
                        ->addRouterAttributesToFormRequest($routerAttributesToFormRequest)
                        ->addRouterAttributesToFormMetadata($routerAttributesToFormMetadata)
                        ->setTabCondition($tabCondition)
                        ->setPreviewCondition($previewCondition);
                }

                if (PageAdmin::EDIT_FORM_VIEW . '.seo' === $viewBuilder->getName()) {
                    $viewBuilder
                        ->disablePreviewWebspaceChooser()
                        ->addRouterAttributesToFormRequest($routerAttributesToFormRequest)
                        ->setPreviewCondition($previewCondition);
                }

                if (PageAdmin::EDIT_FORM_VIEW . '.excerpt' === $viewBuilder->getName()) {
                    $viewBuilder
                        ->disablePreviewWebspaceChooser()
                        ->addRouterAttributesToFormRequest($routerAttributesToFormRequest)
                        ->addRouterAttributesToFormMetadata($routerAttributesToFormMetadata)
                        ->setPreviewCondition($previewCondition)
                        ->setTabCondition($tabCondition);
                }

                if (PageAdmin::EDIT_FORM_VIEW . '.settings' === $viewBuilder->getName()) {
                    $viewBuilder
                        ->disablePreviewWebspaceChooser()
                        ->addRouterAttributesToFormRequest($routerAttributesToFormRequest)
                        ->setPreviewCondition($previewCondition);
                }

                $viewCollection->add($viewBuilder);
            }

            if ($this->activityViewBuilderFactory->hasActivityListPermission()) {
                $insightsResourceTabViewName = PageAdmin::EDIT_FORM_VIEW . '.insights';
                $viewCollection->add(
                    $this->activityViewBuilderFactory
                        ->createActivityListViewBuilder(
                            $insightsResourceTabViewName . '.activity',
                            '/activities',
                            PageInterface::RESOURCE_KEY
                        )
                        ->setParent($insightsResourceTabViewName)
                );
            }

            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createPreviewFormViewBuilder('sulu_page.page_edit_form.permissions', '/permissions')
                    ->setResourceKey('permissions')
                    ->setPreviewResourceKey(PageInterface::RESOURCE_KEY)
                    ->setFormKey('permission_details')
                    ->addRequestParameters(['resourceKey' => PageInterface::RESOURCE_KEY])
                    ->setTabCondition('_permissions.security')
                    ->setTabTitle('sulu_security.permissions')
                    ->addToolbarActions([
                        new SaveWithFormDialogToolbarAction(
                            'sulu_security.inherit_permissions_title',
                            'permission_inheritance',
                            '__parent.hasSub'
                        ),
                    ])
                    ->addRouterAttributesToFormRequest(['webspace'])
                    ->setTitleVisible(true)
                    ->setTabOrder(5120)
                    ->setParent(static::EDIT_FORM_VIEW)
            );
        }
    }

    public function getSecurityContexts()
    {
        $webspaceSecuritySystemContexts = [];

        /** @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $security = $webspace->getSecurity();
            if (!$security) {
                continue;
            }

            $system = $security->getSystem();
            if (!$system) {
                continue;
            }

            $webspaceSecuritySystemContexts[$system] = [
                self::SECURITY_CONTEXT_GROUP => [
                    self::SECURITY_CONTEXT_PREFIX . $webspace->getKey() => [
                        PermissionTypes::VIEW,
                    ],
                ],
            ];
        }

        /** @var array<string, array<string>> $webspaceContexts */
        $webspaceContexts = [];
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            /* @var Webspace $webspace */
            $webspaceContexts[self::getPageSecurityContext($webspace->getKey())] = [
                PermissionTypes::VIEW,
                PermissionTypes::ADD,
                PermissionTypes::EDIT,
                PermissionTypes::DELETE,
                PermissionTypes::LIVE,
                PermissionTypes::SECURITY,
            ];
        }

        return \array_merge(
            [
                self::SULU_ADMIN_SECURITY_SYSTEM => [
                    'Webspaces' => $webspaceContexts,
                ],
            ],
            $webspaceSecuritySystemContexts
        );
    }

    public function getSecurityContextsWithPlaceholder()
    {
        $webspaceSecuritySystemContexts = [];

        /** @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $security = $webspace->getSecurity();
            if (!$security) {
                continue;
            }

            $system = $security->getSystem();
            if (!$system) {
                continue;
            }

            $webspaceSecuritySystemContexts[$system] = [
                self::SECURITY_CONTEXT_GROUP => [
                    self::SECURITY_CONTEXT_PREFIX . '#webspace#' => [
                        PermissionTypes::VIEW,
                    ],
                ],
            ];
        }

        return \array_merge(
            [
                self::SULU_ADMIN_SECURITY_SYSTEM => [
                    self::SECURITY_CONTEXT_GROUP => [
                        self::SECURITY_CONTEXT_PREFIX . '#webspace#' => [
                            PermissionTypes::VIEW,
                            PermissionTypes::ADD,
                            PermissionTypes::EDIT,
                            PermissionTypes::DELETE,
                            PermissionTypes::LIVE,
                            PermissionTypes::SECURITY,
                        ],
                    ],
                ],
            ],
            $webspaceSecuritySystemContexts
        );
    }

    public function getConfigKey(): string
    {
        return 'sulu_page';
    }

    /**
     * @return array{
     *     teaser: array<string, mixed>,
     *     versioning: bool,
     *     webspaces: array<string, Webspace>
     * }
     */
    public function getConfig(): array
    {
        $webspaces = $this->webspaceManager->getWebspaceCollection()->getWebspaces();
        \uasort($webspaces, function($w1, $w2) {
            return \strcmp($w1->getName(), $w2->getName());
        });

        return [
            'teaser' => [], // $this->teaserProviderPool->getConfiguration(),
            'versioning' => true, //$this->versioningEnabled,
            'webspaces' => $webspaces,
        ];
    }

    private function getFirstWebspaceWithPermissions(): ?Webspace
    {
        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            if ($this->securityChecker->hasPermission(
                self::getPageSecurityContext($webspace->getKey()),
                PermissionTypes::EDIT
            )) {
                return $webspace;
            }
        }

        return null;
    }

    /**
     * Returns security context for pages in given webspace.
     *
     * @final
     */
    public static function getPageSecurityContext(string $webspaceKey): string
    {
        return \sprintf('%s%s', self::SECURITY_CONTEXT_PREFIX, $webspaceKey);
    }
}
