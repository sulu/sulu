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
     * Returns all the registered admins.
     *
     * @return Admin[]
     */
    public function getAdmins()
    {
        return $this->pool;
    }

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
     * Returns the navigation combined from all admin-objects.
     *
     * @return Navigation
     */
    public function getNavigation()
    {
        /** @var Navigation $navigation */
        $navigation = null;
        foreach ($this->pool as $admin) {
            /* @var Admin $admin */
            if ($navigation == null) {
                $navigation = $admin->getNavigation();
            } else {
                $navigation = $navigation->merge($admin->getNavigation());
            }
        }

        return $navigation;
    }

    public function getSecurityContexts()
    {
        $contexts = [];
        foreach ($this->pool as $admin) {
            /* @var Admin $admin */
            $contexts = array_merge_recursive($contexts, $admin->getSecurityContexts());
        }

        return $contexts;
    }
}
