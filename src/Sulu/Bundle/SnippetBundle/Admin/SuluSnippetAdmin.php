<?php
/*
 * This file is part of the Sulu CMS.
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

/**
 * Admin for snippet
 */
class SuluSnippetAdmin extends Admin
{
    /**
     * Constructor
     */
    public function __construct($title)
    {
        $rootNavigationItem = new NavigationItem($title);

        $section = new NavigationItem('navigation.webspaces');
        $rootNavigationItem->addChild($section);

        $header = new NavigationItem('navigation.global-content');
        $header->setIcon('bullseye');
        $section->addChild($header);

        $item = new NavigationItem('navigation.snippets');
        $item->setIcon('bullseye');
        $item->setAction('snippet/snippets');
        $header->addChild($item);

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
        return 'sulusnippet';
    }
}
