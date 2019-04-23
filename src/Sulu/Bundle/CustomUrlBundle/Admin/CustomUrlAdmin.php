<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

/**
 * Includes custom-url-bundle into sulu admin.
 */
class CustomUrlAdmin extends Admin
{
    /**
     * Returns security context for custom-urls in given webspace.
     *
     * @param string $webspaceKey
     *
     * @return string
     */
    public static function getCustomUrlSecurityContext($webspaceKey)
    {
        return sprintf('%s%s.%s', PageAdmin::SECURITY_CONTEXT_PREFIX, $webspaceKey, 'custom-urls');
    }

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var RouteBuilderFactoryInterface
     */
    private $routeBuilderFactory;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        RouteBuilderFactoryInterface $routeBuilderFactory
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->routeBuilderFactory = $routeBuilderFactory;
    }

    public function getRoutes(): array
    {
        $listToolbarActions = [
            'sulu_admin.add',
            'sulu_admin.delete',
        ];

        return [
            $this->routeBuilderFactory
                ->createFormOverlayListRouteBuilder('sulu_custom_url.custom_urls_list', '/custom-urls')
                ->setResourceKey('custom_urls')
                ->setListKey('custom_urls')
                ->addListAdapters(['table_light'])
                ->addRouterAttributesToListStore(['webspace'])
                ->addRouterAttributesToFormStore(['webspace'])
                ->disableSearching()
                ->setFormKey('custom_url_details')
                ->setTabTitle('sulu_custom_url.custom_urls')
                ->addToolbarActions($listToolbarActions)
                ->setTabOrder(1536)
                ->setParent(PageAdmin::WEBSPACE_TABS_ROUTE)
                ->addRerenderAttribute('webspace')
                ->getRoute(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        $webspaceContexts = [];
        /* @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $securityContextKey = self::getCustomUrlSecurityContext($webspace->getKey());
            $webspaceContexts[$securityContextKey] = $this->getSecurityContextPermissions();
        }

        return [
             'Sulu' => [
                 'Webspaces' => $webspaceContexts,
             ],
         ];
    }

    public function getSecurityContextsWithPlaceholder()
    {
        return [
            'Sulu' => [
                'Webspaces' => [
                    self::getCustomUrlSecurityContext('#webspace#') => $this->getSecurityContextPermissions(),
                ],
            ],
        ];
    }

    private function getSecurityContextPermissions()
    {
        return [
            PermissionTypes::VIEW,
            PermissionTypes::ADD,
            PermissionTypes::EDIT,
            PermissionTypes::DELETE,
        ];
    }
}
