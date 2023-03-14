<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
     * @param string|null $path
     * @param bool $resolveConflict
     *
     * @return RouteInterface
     */
    public function create(RoutableInterface $entity, $path = null, $resolveConflict = true);

    /**
     * Creates a new route and handles the histories if the route has changed.
     *
     * @param string|null $path
     * @param bool $resolveConflict
     *
     * @return RouteInterface|null
     *
     * @throws RouteNotCreatedException
     */
    public function update(RoutableInterface $entity, $path = null, $resolveConflict = true);

    /**
     * Creates a new route and handles the histories if the route has changed.
     */
    public function createOrUpdateByAttributes(
        string $entityClass,
        string $id,
        string $locale,
        string $path,
        $resolveConflict = true
    ): RouteInterface;
}
