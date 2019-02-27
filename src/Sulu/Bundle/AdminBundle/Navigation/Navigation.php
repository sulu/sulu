<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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

    public function __construct(NavigationItem $root = null)
    {
        if (null == $root) {
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
     *
     * @param Navigation $navigation
     *
     * @return Navigation
     */
    public function merge(self $navigation)
    {
        return new self($this->getRoot()->merge($navigation->getRoot()));
    }

    /**
     * Returns the navigation as array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getRoot()->toArray();
    }
}
