<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsocketBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;

/**
 * Defines all the required information for websocket bundle.
 */
class WebsocketAdmin extends Admin
{
    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'suluwebsocket';
    }

    /**
     * {@inheritdoc}
     */
    public function getNavigation()
    {
        return new Navigation();
    }
}
