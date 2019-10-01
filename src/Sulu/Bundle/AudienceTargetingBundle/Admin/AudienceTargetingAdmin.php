<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AudienceTargetingBundle\Rule\RuleCollectionInterface;
use Sulu\Bundle\AudienceTargetingBundle\Rule\RuleInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

/**
 * Integrates audience targeting into sulu-admin.
 */
class AudienceTargetingAdmin extends Admin
{
    const SECURITY_CONTEXT = 'sulu.settings.target-groups';

    const LIST_ROUTE = 'sulu_audience_targeting.list';

    const ADD_FORM_ROUTE = 'sulu_audience_targeting.add_form';

    const EDIT_FORM_ROUTE = 'sulu_audience_targeting.edit_form';

    /**
     * @var ViewBuilderFactoryInterface
     */
    private $viewBuilderFactory;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var RuleCollectionInterface
     */
    private $ruleCollection;

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        RuleCollectionInterface $ruleCollection,
        SecurityCheckerInterface $securityChecker
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->ruleCollection = $ruleCollection;
        $this->securityChecker = $securityChecker;
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $targetGroups = new NavigationItem('sulu_audience_targeting.target_groups');
            $targetGroups->setPosition(10);
            $targetGroups->setView(static::LIST_ROUTE);

            $navigationItemCollection->get(Admin::SETTINGS_NAVIGATION_ITEM)->addChild($targetGroups);
        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $listToolbarActions = [];
        $formToolbarActions = [];

        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::ADD)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.add');
        }

        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $formToolbarActions[] = new ToolbarAction('sulu_admin.save');
        }

        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::DELETE)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.delete');
            $formToolbarActions[] = new ToolbarAction('sulu_admin.delete');
        }

        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.export');
        }

        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $viewCollection->add(
                $this->viewBuilderFactory->createListViewBuilder(static::LIST_ROUTE, '/target-groups')
                    ->setResourceKey('target_groups')
                    ->setListKey('target_groups')
                    ->setTitle('sulu_audience_targeting.target_groups')
                    ->addListAdapters(['table'])
                    ->setAddView(static::ADD_FORM_ROUTE)
                    ->setEditView(static::EDIT_FORM_ROUTE)
                    ->addToolbarActions($listToolbarActions)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createResourceTabViewBuilder(static::ADD_FORM_ROUTE, '/target-groups/add')
                    ->setResourceKey('target_groups')
                    ->setBackView(static::LIST_ROUTE)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_audience_targeting.add_form.details', '/details')
                    ->setResourceKey('target_groups')
                    ->setFormKey('target_group_details')
                    ->setTabTitle('sulu_admin.details')
                    ->setEditView(static::EDIT_FORM_ROUTE)
                    ->addToolbarActions($formToolbarActions)
                    ->setParent(static::ADD_FORM_ROUTE)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createResourceTabViewBuilder(static::EDIT_FORM_ROUTE, '/target-groups/:id')
                    ->setResourceKey('target_groups')
                    ->setBackView(static::LIST_ROUTE)
                    ->setTitleProperty('title')
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_audience_targeting.edit_form.details', '/details')
                    ->setResourceKey('target_groups')
                    ->setFormKey('target_group_details')
                    ->setTabTitle('sulu_admin.details')
                    ->addToolbarActions($formToolbarActions)
                    ->setParent(static::EDIT_FORM_ROUTE)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        return [
            'Sulu' => [
                'Settings' => [
                    self::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                ],
            ],
        ];
    }

    public function getConfigKey(): ?string
    {
        return 'sulu_audience_targeting';
    }

    public function getConfig(): ?array
    {
        return [
            'targetGroupRules' => array_map(function(RuleInterface $rule) {
                $type = $rule->getType();

                return [
                    'name' => $rule->getName(),
                    'type' => [
                        'name' => $type->getName(),
                        'options' => $type->getOptions(),
                    ],
                ];
            }, $this->ruleCollection->getRules()),
        ];
    }
}
