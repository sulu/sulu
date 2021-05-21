<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin;

use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ListItemAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ActivityAdmin extends Admin
{
    const SECURITY_CONTEXT = 'sulu.activities.activities';

    const LIST_VIEW = 'sulu_activity.activities.list';

    const EDIT_FORM_ACTIVITY_VERSION_TAB_VIEW = 'sulu_page.page_edit_form.activity_version_tab';

    /**
     * @var ViewBuilderFactoryInterface
     */
    private $viewBuilderFactory;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var bool
     */
    private $versioningEnabled;

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        bool $versioningEnabled
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->versioningEnabled = $versioningEnabled;
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if (!$this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            return;
        }

        $activitiesNavigationItem = new NavigationItem('sulu_activity.activities');
        $activitiesNavigationItem->setPosition(100);
        $activitiesNavigationItem->setView(static::LIST_VIEW);

        $navigationItemCollection->get(Admin::SETTINGS_NAVIGATION_ITEM)->addChild($activitiesNavigationItem);
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        if (!$this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            return;
        }

        $viewCollection->add(
            $this->viewBuilderFactory->createListViewBuilder(static::LIST_VIEW, '/activities')
                ->setResourceKey(ActivityInterface::RESOURCE_KEY)
                ->setListKey('activities')
                ->setTitle('sulu_activity.activities')
                ->addListAdapters(['table'])
                ->disableSearching()
                ->disableSelection()
                ->disableColumnOptions()
                ->disableFiltering()
                ->addMetadataRequestParameters(['showResource' => true])
                ->addAdapterOptions([
                    'table' => [
                        'skin' => 'flat',
                        'show_header' => false,
                    ],
                ])
                ->addToolbarActions([])
        );

        if ($viewCollection->has(PageAdmin::EDIT_FORM_VIEW)) {
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createResourceTabViewBuilder(static::EDIT_FORM_ACTIVITY_VERSION_TAB_VIEW, '/activity')
                    ->setResourceKey(BasePageDocument::RESOURCE_KEY)
                    ->setTabOrder(6144)
                    ->setTabTitle($this->versioningEnabled ? 'sulu_admin.activity_versions' : 'sulu_admin.activity')
                    ->setParent(PageAdmin::EDIT_FORM_VIEW)
            );

            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createListViewBuilder(static::EDIT_FORM_ACTIVITY_VERSION_TAB_VIEW . '.activity', '/activity')
                    ->setTabTitle('sulu_admin.activity')
                    ->setResourceKey(ActivityInterface::RESOURCE_KEY)
                    ->setListKey('activities')
                    ->addListAdapters(['table'])
                    ->addAdapterOptions([
                        'table' => [
                            'skin' => 'flat',
                            'show_header' => false,
                        ],
                    ])
                    ->disableTabGap()
                    ->disableSearching()
                    ->disableSelection()
                    ->disableColumnOptions()
                    ->disableFiltering()
                    ->addRouterAttributesToListRequest(['id' => 'resourceId'])
                    ->addRequestParameters(['resourceKey' => BasePageDocument::RESOURCE_KEY])
                    ->setParent(static::EDIT_FORM_ACTIVITY_VERSION_TAB_VIEW)
            );

            if ($this->versioningEnabled) {
                $viewCollection->add(
                    $this->viewBuilderFactory
                        ->createListViewBuilder(static::EDIT_FORM_ACTIVITY_VERSION_TAB_VIEW . '.versions', '/versions')
                        ->setTabTitle('sulu_admin.versions')
                        ->setResourceKey('page_versions')
                        ->setListKey('page_versions')
                        ->addListAdapters(['table'])
                        ->addAdapterOptions([
                            'table' => [
                                'skin' => 'flat',
                            ],
                        ])
                        ->disableTabGap()
                        ->disableSearching()
                        ->disableSelection()
                        ->disableColumnOptions()
                        ->disableFiltering()
                        ->addRouterAttributesToListRequest(['id', 'webspace'])
                        ->addItemActions([
                            new ListItemAction('restore_version', ['success_view' => PageAdmin::EDIT_FORM_VIEW])
                        ])
                        ->setParent(static::EDIT_FORM_ACTIVITY_VERSION_TAB_VIEW)
                );

                $viewCollection->add(
                    $this->viewBuilderFactory
                        ->createFormViewBuilder(static::EDIT_FORM_ACTIVITY_VERSION_TAB_VIEW . '.versions-old', '/versions-old')
                        ->setTabTitle('sulu_admin.versions')
                        ->setResourceKey(BasePageDocument::RESOURCE_KEY)
                        ->setFormKey('page_versions')
                        ->addToolbarActions([])
                        ->addRouterAttributesToFormRequest(['webspace'])
                        ->disableTabGap()
                        ->setParent(static::EDIT_FORM_ACTIVITY_VERSION_TAB_VIEW)
                );
            }
        }

        if ($viewCollection->has(MediaAdmin::EDIT_FORM_VIEW)) {
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createListViewBuilder('sulu_media.form.activity', '/activity')
                    ->setTabTitle('sulu_admin.activity')
                    ->setResourceKey(ActivityInterface::RESOURCE_KEY)
                    ->setListKey('activities')
                    ->addListAdapters(['table'])
                    ->addAdapterOptions([
                        'table' => [
                            'skin' => 'flat',
                            'show_header' => false,
                        ],
                    ])
                    ->disableTabGap()
                    ->disableSearching()
                    ->disableSelection()
                    ->disableColumnOptions()
                    ->disableFiltering()
                    ->addResourceStorePropertiesToListRequest(['id' => 'resourceId'])
                    ->addRequestParameters(['resourceKey' => MediaInterface::RESOURCE_KEY])
                    ->setParent(MediaAdmin::EDIT_FORM_VIEW)
            );
        }
    }

    public function getSecurityContexts()
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'Activities' => [
                    static::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                    ],
                ],
            ],
        ];
    }

    public static function getPriority(): int
    {
        return \min(PageAdmin::getPriority(), MediaAdmin::getPriority()) - 1;
    }
}
