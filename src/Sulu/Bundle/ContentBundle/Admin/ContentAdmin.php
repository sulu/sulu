<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class ContentAdmin extends Admin
{
    /**
     * The prefix for the security context, the key of the webspace has to be appended.
     *
     * @var string
     */
    const SECURITY_CONTEXT_PREFIX = 'sulu.webspaces.';

    /**
     * The prefix for the settings security context, the key of the webspace has to be appended.
     *
     * @var string
     */
    const SECURITY_SETTINGS_CONTEXT_PREFIX = 'sulu.webspace_settings.';

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        SecurityCheckerInterface $securityChecker,
        SessionManagerInterface $sessionManager
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->securityChecker = $securityChecker;
        $this->sessionManager = $sessionManager;
    }

    public function getNavigation(): Navigation
    {
        $rootNavigationItem = $this->getNavigationItemRoot();

        /** @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT_PREFIX . $webspace->getKey(), PermissionTypes::VIEW)) {
                $webspaceItem = new NavigationItem('sulu_content.webspaces');
                $webspaceItem->setPosition(10);
                $webspaceItem->setIcon('su-webspace');
                $webspaceItem->setMainRoute('sulu_content.webspaces');

                $rootNavigationItem->addChild($webspaceItem);

                break;
            }
        }

        return new Navigation($rootNavigationItem);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        /** @var Webspace $firstWebspace */
        $firstWebspace = current($this->webspaceManager->getWebspaceCollection()->getWebspaces());

        $formToolbarActionsWithType = [
            'sulu_admin.save_with_publishing',
            'sulu_admin.type',
            'sulu_admin.delete',
            'sulu_content.edit',
        ];

        $formToolbarActionsWithoutType = [
            'sulu_admin.save_with_publishing',
        ];

        $previewExpression = 'nodeType == 1';

        return [
            (new Route('sulu_content.webspaces', '/webspaces/:webspace/:locale', 'sulu_content.webspace_overview'))
                ->addAttributeDefault('webspace', $firstWebspace->getKey())
                ->addAttributeDefault('locale', $firstWebspace->getDefaultLocalization()->getLocale())
                ->addRerenderAttribute('webspace'),
            (new Route('sulu_content.page_add_form', '/webspaces/:webspace/:locale/add/:parentId', 'sulu_content.page_tabs'))
                ->addOption('resourceKey', 'pages')
                ->addOption('backRoute', 'sulu_content.webspaces')
                ->addOption('routerAttributesToFormStore', ['parentId', 'webspace'])
                ->addOption('toolbarActions', $formToolbarActionsWithoutType),
            (new Route('sulu_content.page_add_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_content.page_form_detail')
                ->addOption('editRoute', 'sulu_content.page_edit_form.detail')
                ->addOption('routerAttributesToEditRoute', ['webspace'])
                ->addOption('toolbarActions', $formToolbarActionsWithType)
                ->setParent('sulu_content.page_add_form'),
            (new Route('sulu_content.page_edit_form', '/webspaces/:webspace/:locale/:id', 'sulu_content.page_tabs'))
                ->addOption('resourceKey', 'pages')
                ->addOption('backRoute', 'sulu_content.webspaces')
                ->addOption('routerAttributesToFormStore', ['parentId', 'webspace'])
                ->addOption('toolbarActions', $formToolbarActionsWithoutType),
            (new Route('sulu_content.page_edit_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_content.page_form_detail')
                ->addOption('toolbarActions', $formToolbarActionsWithType)
                ->addOption('preview', $previewExpression)
                ->setParent('sulu_content.page_edit_form'),
            (new Route('sulu_content.page_edit_form.seo', '/seo', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_content.page_form_seo')
                ->addOption('resourceKey', 'pages_seo')
                ->setParent('sulu_content.page_edit_form'),
            (new Route('sulu_content.page_edit_form.excerpt', '/excerpt', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_content.page_form_excerpt')
                ->addOption('resourceKey', 'pages_excerpt')
                ->setParent('sulu_content.page_edit_form'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        $webspaceContexts = [];
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            /* @var Webspace $webspace */
            $webspaceContexts[self::SECURITY_CONTEXT_PREFIX . $webspace->getKey()] = [
                PermissionTypes::VIEW,
                PermissionTypes::ADD,
                PermissionTypes::EDIT,
                PermissionTypes::DELETE,
                PermissionTypes::LIVE,
                PermissionTypes::SECURITY,
            ];
        }

        return [
            'Sulu' => [
                'Webspaces' => $webspaceContexts,
            ],
        ];
    }
}
