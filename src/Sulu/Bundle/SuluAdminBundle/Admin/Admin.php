<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Bundle\AdminBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\Navigation;

/**
 * Defines all the required information from a bundle's admin class.
 *
 * @package Sulu\Bundle\AdminBundle\Admin
 */
abstract class Admin
{
    /**
     * The navigation describes the position of the admin
     * @var Navigation
     */
    protected $navigation;

    /**
     * Sets the navigation containing the position of the admin in the navigation
     * @param \Sulu\Bundle\AdminBundle\Navigation\Navigation $navigation
     */
    public function setNavigation($navigation)
    {
        $this->navigation = $navigation;
    }

    /**
     * Returns a navigation containing the position of the admin in the navigation
     * @return \Sulu\Bundle\AdminBundle\Navigation\Navigation
     */
    public function getNavigation()
    {
        return $this->navigation;
    }

    /**
     * Returns all the commands available in a concrete admin.
     * An empty array is the default value (no commands available), and the concrete class
     * can override this method, to add all the available commands.
     * @return array
     */
    public function getCommands()
    {
        return array();
    }

    /**
     * Returns all the security contexts, which are available in the concrete bundle.
     * @return array
     */
    public function getSecurityContexts()
    {
        return array();
    }
}
