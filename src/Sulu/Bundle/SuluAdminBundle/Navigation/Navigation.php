<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Navigation;


class Navigation
{
    /**
     * @var NavigationItem
     */
    protected $root;

    function __construct(NavigationItem $root = null)
    {
        if ($root == null) {
            $root = new NavigationItem('');
        }
        $this->root = $root;
    }

    /**
     * @return NavigationItem
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Merges the given navigation with this one and returns the result.
     * Works only if there are no duplicate of items in the same level.
     * @param Navigation $navigation
     * @return Navigation
     */
    public function merge(Navigation $navigation)
    {
        return new Navigation($this->getRoot()->merge($navigation->getRoot()));
    }
}