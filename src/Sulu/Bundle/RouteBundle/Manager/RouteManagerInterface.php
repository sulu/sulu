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

use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;

/**
 * Defines the interface to interact with routes.
 */
interface RouteManagerInterface
{
    /**
     * Returns a newly creates route for given routable-entity.
     *
     * @param RoutableInterface $entity
     * @param string|null $path
     *
     * @throws RouteAlreadyCreatedException
     *
     * @return RouteInterface
     */
    public function create(RoutableInterface $entity, $path = null);

    /**
     * Creates a new route and handles the histories if the route has changed.
     *
     * @param RoutableInterface $entity
     * @param string|null $path
     *
     * @throws RouteNotCreatedException
     *
     * @return RouteInterface|null
     */
    public function update(RoutableInterface $entity, $path = null);
}
