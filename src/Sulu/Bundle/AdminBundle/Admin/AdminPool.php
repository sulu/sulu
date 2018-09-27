<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\Navigation;

/**
 * The AdminPool is a container for all the registered admin-objects.
 */
class AdminPool
{
    /**
     * The array for all the admin-objects.
     *
     * @var array
     */
    private $pool = [];

    /**
     * Adds a new admin.
     *
     * @param $admin
     */
    public function addAdmin($admin)
    {
        $this->pool[] = $admin;
    }

    /**
     * @return Admin[]
     */
    public function getAdmins(): array
    {
        return $this->pool;
    }

    /**
     * Returns the navigation combined from all Admin objects.
     *
     * @return Navigation
     */
    public function getNavigation()
    {
        /** @var Navigation $navigation */
        $navigation = null;
        $this->iterateAdmins(function(Admin $admin) use (&$navigation) {
            if (null === $navigation) {
                $navigation = $admin->getNavigation();

                return;
            }
            $navigation = $navigation->merge($admin->getNavigation());
        });

        return $navigation;
    }

    /**
     * Returns the combined security contexts from all Admin objects.
     *
     * @return array
     */
    public function getSecurityContexts()
    {
        $contexts = [];
        $this->iterateAdmins(function(Admin $admin) use (&$contexts) {
            $contexts = array_merge_recursive($contexts, $admin->getSecurityContexts());
        });

        return $contexts;
    }

    public function getSecurityContextsWithPlaceholder()
    {
        $contexts = [];
        $this->iterateAdmins(function(Admin $admin) use (&$contexts) {
            $contexts = array_merge_recursive($contexts, $admin->getSecurityContextsWithPlaceholder());
        });

        return $contexts;
    }

    /**
     * Helper function to iterate over all available Admin objects.
     */
    private function iterateAdmins(callable $callback): void
    {
        foreach ($this->pool as $admin) {
            $callback($admin);
        }
    }
}
