<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AutomationBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

/**
 * Admin integration of the bundle.
 */
class AutomationAdmin extends Admin
{
    /**
     * @param string $title
     */
    public function __construct($title)
    {
        $this->setNavigation(new Navigation(new NavigationItem($title)));
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'suluautomation';
    }
}
