<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\Navigation;

use Sulu\Bundle\AdminBundle\Exception\NavigationItemNotFoundException;

class NavigationItemCollection
{
    /**
     * @var NavigationItem[]
     */
    private $navigationItems = [];

    public function add(NavigationItem $navigationItem): void
    {
        $this->navigationItems[$navigationItem->getName()] = $navigationItem;
    }

    public function get(string $navigationItemName): NavigationItem
    {
        if (!\array_key_exists($navigationItemName, $this->navigationItems)) {
            throw new NavigationItemNotFoundException($navigationItemName);
        }

        return $this->navigationItems[$navigationItemName];
    }

    public function has(string $navigationItemName): bool
    {
        return \array_key_exists($navigationItemName, $this->navigationItems);
    }

    /**
     * @return NavigationItem[]
     */
    public function all(): array
    {
        return $this->navigationItems;
    }

    public function remove(string $navigationItemName): void
    {
        if (!\array_key_exists($navigationItemName, $this->navigationItems)) {
            throw new NavigationItemNotFoundException($navigationItemName);
        }

        unset($this->navigationItems[$navigationItemName]);
    }
}
