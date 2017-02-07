<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Manager;

use Sulu\Bundle\RouteBundle\Model\RouteInterface;

/**
 * Defines an interface for resolving route conflicts.
 */
interface ConflictResolverInterface
{
    /**
     * Resolves path conflicts.
     *
     * @param RouteInterface $route
     *
     * @return RouteInterface
     */
    public function resolve(RouteInterface $route);
}
