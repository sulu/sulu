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
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Bundle\ContentBundle\Admin\ContentAdmin;
use Sulu\Component\Localization\Localization;
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
    public function getRoutes(): array
    {
        $snippetLocales = array_values(
            array_map(
                function(Localization $localization) {
                    return $localization->getLocale();
                },
                $this->webspaceManager->getAllLocalizations()
            )
        );

        return [
            (new Route('sulu_snippet.datagrid', '/snippets/:locale', 'sulu_admin.datagrid'))
                ->addOption('title', 'sulu_snippet.snippets')
                ->addOption('resourceKey', 'snippets')
                ->addOption('adapters', ['table'])
                ->addOption('addRoute', 'sulu_snippet.add_form.detail')
                ->addOption('editRoute', 'sulu_snippet.edit_form.detail')
                ->addOption('locales', $snippetLocales)
                ->addAttributeDefault('locale', $snippetLocales[0]),
            (new Route('sulu_snippet.add_form', '/snippets/:locale/add', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'snippets')
                ->addOption('locales', $snippetLocales),
            (new Route('sulu_snippet.add_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_snippet.details')
                ->addOption('backRoute', 'sulu_snippet.datagrid')
                ->addOption('editRoute', 'sulu_snippet.edit_form.detail')
                ->setParent('sulu_snippet.add_form'),
            (new Route('sulu_snippet.edit_form', '/snippets/:locale/:id', 'sulu_admin.resource_tabs'))
                ->addOption('resourceKey', 'snippets')
                ->addOption('locales', $snippetLocales),
            (new Route('sulu_snippet.edit_form.detail', '/details', 'sulu_admin.form'))
                ->addOption('tabTitle', 'sulu_snippet.details')
                ->addOption('backRoute', 'sulu_snippet.datagrid')
                ->setParent('sulu_snippet.edit_form'),
            (new Route('sulu_snippet.edit_form.taxonomies', '/taxonomies', 'sulu_admin.datagrid'))
                ->addOption('resourceKey', 'snippets')
                ->addOption('tabTitle', 'sulu_snippet.taxonomies')
                ->addOption('adapters', ['table'])
                ->addOption('backRoute', 'sulu_snippet.datagrid')
                ->setParent('sulu_snippet.edit_form'),
        ];
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
