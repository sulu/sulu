<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Bundle\SecurityBundle\Permission\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SuluContentAdmin extends Admin
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
     * The prefix for the security context, the key of the webspace has to be appended
     * @var string
     */
    private $securityContextPrefix = 'sulu.webspaces.';

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        SecurityCheckerInterface $securityChecker,
        $title
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->securityChecker = $securityChecker;

        $rootNavigationItem = new NavigationItem($title);

        $section = new NavigationItem('navigation.webspaces');

        $rootNavigationItem->addChild($section);

        /** @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            try {
                // check if the user has at least view permission for the given webspace
                $this->securityChecker->checkPermission($this->securityContextPrefix . $webspace->getKey(), 'view');

                $webspaceItem = new NavigationItem($webspace->getName());
                $webspaceItem->setIcon('bullseye');

                $contentItem = new NavigationItem('navigation.webspaces.content');
                $contentItem->setAction('content/contents/' . $webspace->getKey());
                $webspaceItem->addChild($contentItem);

                $indexPageItem = new NavigationItem('navigation.webspaces.index-page');
                $indexPageItem->setAction(
                    'content/contents/' . $webspace->getKey() . '/edit:index/details'
                );
                $webspaceItem->addChild($indexPageItem);

                $section->addChild($webspaceItem);
            } catch (AccessDeniedException $e) {
                // don't add the entry, if the user is not allowed to see it
            }
        }

        $this->setNavigation(new Navigation($rootNavigationItem));
    }

    /**
     * {@inheritdoc}
     */
    public function getCommands()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'sulucontent';
    }

    /**
     * {@inheritDoc}
     */
    public function getSecurityContexts()
    {
        $webspaceContexts = array();
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            /** @var Webspace $webspace */
            $webspaceContexts[] = $this->securityContextPrefix . $webspace->getKey();
        }

        return array(
            'Sulu' => array(
                'Webspaces' => $webspaceContexts
            )
        );
    }
}
