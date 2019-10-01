<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Admin\View\TogglerToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SecurityAdmin extends Admin
{
    const ROLE_SECURITY_CONTEXT = 'sulu.security.roles';

    const GROUP_SECURITY_CONTEXT = 'sulu.security.groups';

    const USER_SECURITY_CONTEXT = 'sulu.security.users';

    const LIST_VIEW = 'sulu_security.roles_list';

    const ADD_FORM_VIEW = 'sulu_security.role_add_form';

    const EDIT_FORM_VIEW = 'sulu_security.role_edit_form';

    /**
     * @var ViewBuilderFactoryInterface
     */
    private $viewBuilderFactory;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $resources;

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
        array $resources
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->resources = $resources;
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(static::ROLE_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $roles = new NavigationItem('sulu_security.roles');
            $roles->setPosition(10);
            $roles->setView(static::LIST_VIEW);

            $navigationItemCollection->get(Admin::SETTINGS_NAVIGATION_ITEM)->addChild($roles);
        }
    }

    public function getSecurityContexts()
    {
        return [
            'Sulu' => [
                'Security' => [
                    static::ROLE_SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                    static::GROUP_SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                    static::USER_SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                    ],
                ],
            ],
        ];
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $formToolbarActions = [];
        $listToolbarActions = [];

        if ($this->securityChecker->hasPermission(static::ROLE_SECURITY_CONTEXT, PermissionTypes::ADD)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.add');
        }

        if ($this->securityChecker->hasPermission(static::ROLE_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $formToolbarActions[] = new ToolbarAction('sulu_admin.save');
        }

        if ($this->securityChecker->hasPermission(static::ROLE_SECURITY_CONTEXT, PermissionTypes::DELETE)) {
            $formToolbarActions[] = new ToolbarAction('sulu_admin.delete');
            $listToolbarActions[] = new ToolbarAction('sulu_admin.delete');
        }

        if ($this->securityChecker->hasPermission(static::ROLE_SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.export');
        }

        if ($this->securityChecker->hasPermission(static::ROLE_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $viewCollection->add(
                $this->viewBuilderFactory->createListViewBuilder(static::LIST_VIEW, '/roles')
                    ->setResourceKey('roles')
                    ->setListKey('roles')
                    ->setTitle('sulu_security.roles')
                    ->addListAdapters(['table'])
                    ->setAddView(static::ADD_FORM_VIEW)
                    ->setEditView(static::EDIT_FORM_VIEW)
                    ->addToolbarActions($listToolbarActions)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createResourceTabViewBuilder(static::ADD_FORM_VIEW, '/roles/add')
                    ->setResourceKey('roles')
                    ->setBackView(static::LIST_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_security.role_add_form.details', '/details')
                    ->setResourceKey('roles')
                    ->setFormKey('role_details')
                    ->setTabTitle('sulu_admin.details')
                    ->setEditView(static::EDIT_FORM_VIEW)
                    ->addToolbarActions($formToolbarActions)
                    ->setParent(static::ADD_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createResourceTabViewBuilder(static::EDIT_FORM_VIEW, '/roles/:id')
                    ->setResourceKey('roles')
                    ->setBackView(static::LIST_VIEW)
                    ->setTitleProperty('name')
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_security.role_edit_form.details', '/details')
                    ->setResourceKey('roles')
                    ->setFormKey('role_details')
                    ->setTabTitle('sulu_admin.details')
                    ->addToolbarActions($formToolbarActions)
                    ->setParent(static::EDIT_FORM_VIEW)
            );
        }

        if ($this->securityChecker->hasPermission(static::USER_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_security.form.permissions', '/permissions')
                    ->setResourceKey('users')
                    ->setFormKey('user_details')
                    ->setTabTitle('sulu_security.permissions')
                    ->addToolbarActions([
                        new ToolbarAction('sulu_admin.save'),
                        new ToolbarAction('sulu_security.enable_user'),
                        new TogglerToolbarAction(
                            'sulu_security.user_locked',
                            'locked',
                            'lock',
                            'unlock'
                        ),
                    ])
                    ->setIdQueryParameter('contactId')
                    ->setTitleVisible(true)
                    ->setTabOrder(3072)
                    ->setParent(ContactAdmin::CONTACT_EDIT_FORM_VIEW)
            );
        }
    }

    public function getConfigKey(): ?string
    {
        return 'sulu_security';
    }

    public function getConfig(): ?array
    {
        return [
            'endpoints' => [
                'contexts' => $this->urlGenerator->generate('sulu_security.cget_security-contexts'),
            ],
            'resourceKeySecurityContextMapping' => array_filter(array_map(function(array $resource) {
                return $resource['security_context'] ?? null;
            }, $this->resources)),
        ];
    }
}
