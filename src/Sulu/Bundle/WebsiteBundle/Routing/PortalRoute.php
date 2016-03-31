<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Routing;

use Symfony\Component\Routing\Route;

/**
 * Extends symfony route to avoid // in the url.
 */
class PortalRoute extends Route
{
    /**
     * Trims leading slash to avoid "//".
     *
     * @return mixed
     */
    public function getPath()
    {
        return ltrim(parent::getPath(), '/');
    }
}
