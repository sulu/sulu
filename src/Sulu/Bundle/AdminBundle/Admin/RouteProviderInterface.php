<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderInterface;

interface RouteProviderInterface
{
    /**
     * @return RouteBuilderInterface[]
     */
    public function getRoutes(): array;
}
