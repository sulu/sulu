<?php
/**
 * Created by JetBrains PhpStorm.
 * User: danielrotter
 * Date: 19.07.13
 * Time: 14:55
 * To change this template use File | Settings | File Templates.
 */

namespace Sulu\Bundle\AdminBundle\Admin;


/**
 * The AdminPool is a container for all the registrated Admin-objects.
 *
 * @package Sulu\Bundle\AdminBundle\Admin
 */
class AdminPool {
    /**
     * The array for all the admin-objects
     * @var array
     */
    private $pool = array();

    public function addAdmin($admin) { //TODO: force admin
        $this->pool[] = $admin;
    }
}