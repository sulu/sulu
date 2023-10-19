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
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\TogglerToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityAdmin extends Admin
{
    public const ROLE_SECURITY_CONTEXT = 'sulu.security.roles';

    /**
     * @deprecated The group functionality was deprecated in Sulu 2.1 and will be removed in Sulu 3.0
     */
    public const GROUP_SECURITY_CONTEXT = 'sulu.security.groups';

    public const USER_SECURITY_CONTEXT = 'sulu.security.users';

    public const LIST_VIEW = 'sulu_security.roles_list';

    public const ADD_FORM_VIEW = 'sulu_security.role_add_form';

    public const EDIT_FORM_VIEW = 'sulu_security.role_edit_form';

    /**
     * Should be called after ContactAdmin.
     */
    public static function getPriority(): int
    {
        return -1024;
    }

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
     * TODO: Instead of getting the security contexts from the admin pool, that should be closer to the SecurityBundle.
     *
     * @var AdminPool
     */
    private $adminPool;

    /**
     * @var array
     */
    private $resources;

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
        AdminPool $adminPool,
        array $resources
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->adminPool = $adminPool;
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
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'Security' => [
                    self::ROLE_SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                    self::USER_SECURITY_CONTEXT => [
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
                    ->setResourceKey(RoleInterface::RESOURCE_KEY)
                    ->setListKey('roles')
                    ->setTitle('sulu_security.roles')
                    ->addListAdapters(['table'])
                    ->setAddView(static::ADD_FORM_VIEW)
                    ->setEditView(static::EDIT_FORM_VIEW)
                    ->addToolbarActions($listToolbarActions)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createResourceTabViewBuilder(static::ADD_FORM_VIEW, '/roles/add')
                    ->setResourceKey(RoleInterface::RESOURCE_KEY)
                    ->setBackView(static::LIST_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_security.role_add_form.details', '/details')
                    ->setResourceKey(RoleInterface::RESOURCE_KEY)
                    ->setFormKey('role_details')
                    ->setTabTitle('sulu_admin.details')
                    ->setEditView(static::EDIT_FORM_VIEW)
                    ->addToolbarActions($formToolbarActions)
                    ->setParent(static::ADD_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createResourceTabViewBuilder(static::EDIT_FORM_VIEW, '/roles/:id')
                    ->setResourceKey(RoleInterface::RESOURCE_KEY)
                    ->setBackView(static::LIST_VIEW)
                    ->setTitleProperty('name')
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_security.role_edit_form.details', '/details')
                    ->setResourceKey(RoleInterface::RESOURCE_KEY)
                    ->setFormKey('role_details')
                    ->setTabTitle('sulu_admin.details')
                    ->addToolbarActions($formToolbarActions)
                    ->setParent(static::EDIT_FORM_VIEW)
            );
        }

        if ($viewCollection->has(ContactAdmin::CONTACT_EDIT_FORM_VIEW)
            && $this->securityChecker->hasPermission(static::USER_SECURITY_CONTEXT, PermissionTypes::EDIT)
        ) {
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_security.form.permissions', '/permissions')
                    ->setResourceKey(UserInterface::RESOURCE_KEY)
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
            'resourceKeySecurityContextMapping' => \array_filter(\array_map(function(array $resource) {
                return $resource['security_context'] ?? null;
            }, $this->resources)),
            'securityContexts' => $this->adminPool->getSecurityContextsWithPlaceholder(),
            'suluSecuritySystem' => self::SULU_ADMIN_SECURITY_SYSTEM,
        ];
    }
}
