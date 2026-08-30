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

namespace Sulu\Snippet\Infrastructure\Sulu\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;

/**
 * @final
 */
class SnippetAreaAdmin extends Admin
{
    public const LIST_VIEW = 'sulu_snippet.snippet_areas.list';

    public static function getSecurityContext(string $webspaceKey): string
    {
        return \sprintf('%s%s.%s', PageAdmin::SECURITY_CONTEXT_PREFIX, $webspaceKey, 'snippet-areas');
    }

    public function __construct(
        private ViewBuilderFactoryInterface $viewBuilderFactory,
        private SecurityCheckerInterface $securityChecker,
        private WebspaceManagerInterface $webspaceManager,
    ) {
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        if (!$this->hasSomeSnippetAreaPermission()) {
            return;
        }

        $viewCollection->add(
            $this->viewBuilderFactory
                ->createViewBuilder('sulu_snippet.snippet_areas', '/snippet-areas', 'sulu_snippet.snippet_areas')
                ->setOption('snippetEditView', SnippetAdmin::EDIT_TABS_VIEW)
                ->setOption('tabTitle', 'sulu_snippet.webspace_default_snippets')
                ->setOption('tabOrder', 3072)
                ->setOption('toolbarActions', [
                    new ToolbarAction('sulu_website.cache_clear'),
                ])
                ->setParent(PageAdmin::WEBSPACE_TABS_VIEW)
                ->addRerenderAttribute('webspace'),
        );
    }

    public function getSecurityContexts()
    {
        $webspaceContexts = [];
        /* @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $securityContextKey = self::getSecurityContext($webspace->getKey());
            $webspaceContexts[$securityContextKey] = self::getSecurityContextPermissions();
        }

        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'Webspaces' => $webspaceContexts,
            ],
        ];
    }

    public function getSecurityContextsWithPlaceholder(): array
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'Webspaces' => [
                    self::getSecurityContext('#webspace#') => self::getSecurityContextPermissions(),
                ],
            ],
        ];
    }

    /**
     * @return array<int,string>
     */
    private static function getSecurityContextPermissions(): array
    {
        return [
            PermissionTypes::VIEW,
            PermissionTypes::EDIT,
        ];
    }

    private function hasSomeSnippetAreaPermission(): bool
    {
        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            if ($this->securityChecker->hasPermission(
                self::getSecurityContext($webspace->getKey()),
                PermissionTypes::EDIT,
            )) {
                return true;
            }
        }

        return false;
    }
}
