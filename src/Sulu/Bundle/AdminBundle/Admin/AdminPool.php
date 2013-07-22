<?php
/**
 * Created by JetBrains PhpStorm.
 * User: danielrotter
 * Date: 19.07.13
 * Time: 14:55
 * To change this template use File | Settings | File Templates.
 */

namespace Sulu\Bundle\AdminBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\Navigation;

/**
 * The AdminPool is a container for all the registered admin-objects
 *
 * @package Sulu\Bundle\AdminBundle\Admin
 */
class AdminPool
{
    /**
     * The array for all the admin-objects
     * @var array
     */
    private $pool = array();

    /**
     * Returns all the registered admins
     * @return array
     */
    public function getAdmins()
    {
        return $this->pool;
    }

    /**
     * Adds a new admin
     * @param $admin
     */
    public function addAdmin($admin)
    {
        $this->pool[] = $admin;
    }

    /**
     * Returns the navigation combined from all admin-objects
     * @return Navigation
     */
    public function getNavigation()
    {
        $navigation = new Navigation();
        foreach ($this->pool as $admin) {
            /** @var Admin $admin */
            $navigation->merge($admin->getNavigation());
        }

        return $navigation;
    }
}