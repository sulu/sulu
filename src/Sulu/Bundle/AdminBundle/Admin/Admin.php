<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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