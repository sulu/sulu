<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WebsiteAdmin extends Admin
{
    public const ANALYTICS_LIST_VIEW = 'sulu_webspace.analytics_list';

    public function __construct(
        private ViewBuilderFactoryInterface $viewBuilderFactory,
        private WebspaceManagerInterface $webspaceManager,
        private SecurityCheckerInterface $securityChecker,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $listToolbarActions = [
            new ToolbarAction('sulu_admin.add'),
            new ToolbarAction('sulu_admin.delete'),
        ];

        if ($this->hasSomeWebspaceAnalyticsPermission()) {
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormOverlayListViewBuilder(static::ANALYTICS_LIST_VIEW, '/analytics')
                    ->setResourceKey(AnalyticsInterface::RESOURCE_KEY)
                    ->setListKey(AnalyticsInterface::LIST_KEY)
                    ->addListAdapters(['table'])
                    ->addAdapterOptions(['table' => ['skin' => 'light']])
                    ->addRouterAttributesToListRequest(['webspace'])
                    ->addRouterAttributesToFormRequest(['webspace'])
                    ->disableSearching()
                    ->setFormKey('analytic_details')
                    ->setTabTitle('sulu_website.analytics')
                    ->setTabOrder(2048)
                    ->addToolbarActions($listToolbarActions)
                    ->setParent(PageAdmin::WEBSPACE_TABS_VIEW)
                    ->addRerenderAttribute('webspace')
            );
        }
    }

    public function getSecurityContexts()
    {
        $webspaceContexts = [];
        /* @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $securityContextKey = self::getAnalyticsSecurityContext($webspace->getKey());
            $webspaceContexts[$securityContextKey] = $this->getSecurityContextPermissions();

            $cacheSecurityContextKey = self::getCacheSecurityContext($webspace->getKey());
            $webspaceContexts[$cacheSecurityContextKey] = [
                PermissionTypes::DELETE,
            ];
        }

        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'Webspaces' => $webspaceContexts,
            ],
        ];
    }

    public function getSecurityContextsWithPlaceholder()
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'Webspaces' => [
                    self::getAnalyticsSecurityContext('#webspace#') => $this->getSecurityContextPermissions(),
                    self::getCacheSecurityContext('#webspace#') => [
                        PermissionTypes::DELETE,
                    ],
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

    public function getConfigKey(): ?string
    {
        return 'sulu_website';
    }

    public function getConfig(): ?array
    {
        $webspaceCachePermissions = [];
        /* @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $webspaceCachePermissions[$webspace->getKey()] = $this->securityChecker->hasPermission(
                self::getCacheSecurityContext($webspace->getKey()),
                PermissionTypes::DELETE
            );
        }

        return [
            'endpoints' => [
                'clearCache' => $this->urlGenerator->generate('sulu_website.cache.remove'),
            ],
            'webspaceCachePermissions' => $webspaceCachePermissions,
        ];
    }

    private function hasSomeWebspaceAnalyticsPermission(): bool
    {
        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            $hasWebspaceAnalyticsPermission = $this->securityChecker->hasPermission(
                self::getAnalyticsSecurityContext($webspace->getKey()),
                PermissionTypes::EDIT
            );

            if ($hasWebspaceAnalyticsPermission) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns security context for analytics in given webspace.
     */
    public static function getAnalyticsSecurityContext(string $webspaceKey): string
    {
        return \sprintf('%s%s.%s', PageAdmin::SECURITY_CONTEXT_PREFIX, $webspaceKey, 'analytics');
    }

    /**
     * Returns security context for cache clearing in given webspace.
     */
    public static function getCacheSecurityContext(string $webspaceKey): string
    {
        return \sprintf('%s%s.%s', PageAdmin::SECURITY_CONTEXT_PREFIX, $webspaceKey, 'cache');
    }
}
