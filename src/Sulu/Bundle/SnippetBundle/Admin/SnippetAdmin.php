<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Bundle\ContentBundle\Admin\ContentAdmin;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

/**
 * Admin for snippet.
 */
class SnippetAdmin extends Admin
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var bool
     */
    private $defaultEnabled;

    /**
     * Returns security context for default-snippets in given webspace.
     *
     * @param string $webspaceKey
     *
     * @return string
     */
    public static function getDefaultSnippetsSecurityContext($webspaceKey)
    {
        return sprintf('%s%s.%s', ContentAdmin::SECURITY_SETTINGS_CONTEXT_PREFIX, $webspaceKey, 'default-snippets');
    }

    public function __construct(
        SecurityCheckerInterface $securityChecker,
        WebspaceManagerInterface $webspaceManager,
        $defaultEnabled,
        $title
    ) {
        $this->securityChecker = $securityChecker;
        $this->webspaceManager = $webspaceManager;
        $this->defaultEnabled = $defaultEnabled;

        $rootNavigationItem = new NavigationItem($title);

        $section = new NavigationItem('navigation.modules');
        $section->setPosition(20);

        if ($this->securityChecker->hasPermission('sulu.global.snippets', 'view')) {
            $snippet = new NavigationItem('navigation.snippets');
            $snippet->setPosition(10);
            $snippet->setIcon('sticky-note-o');
            $snippet->setAction('snippet/snippets');
            $section->addChild($snippet);
            $rootNavigationItem->addChild($section);
        }

        $this->setNavigation(new Navigation($rootNavigationItem));
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'sulusnippet';
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        $contexts = [
            'Sulu' => [
                'Global' => [
                    'sulu.global.snippets' => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                ],
            ],
        ];

        if ($this->defaultEnabled) {
            $webspaceContexts = [];
            /* @var Webspace $webspace */
            foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
                $webspaceContexts[self::getDefaultSnippetsSecurityContext($webspace->getKey())] = [
                    PermissionTypes::VIEW,
                    PermissionTypes::EDIT,
                ];
            }

            $contexts['Sulu']['Webspace Settings'] = $webspaceContexts;
        }

        return $contexts;
    }
}
