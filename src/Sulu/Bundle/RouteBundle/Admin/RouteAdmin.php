<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;

/**
 * Integrates route-bundle into sulu-admin.
 */
class RouteAdmin extends Admin
{
    public function __construct()
    {
        $this->setNavigation(new Navigation());
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'suluroute';
    }
}
