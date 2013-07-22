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

abstract class Admin
{
    /**
     * @var Navigation
     */
    protected $navigation;

    /**
     * @return \Sulu\Bundle\AdminBundle\Navigation\Navigation
     */
    public function getNavigation()
    {
        return $this->navigation;
    }
}