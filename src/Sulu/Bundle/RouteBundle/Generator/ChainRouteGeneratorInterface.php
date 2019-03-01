<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Generator;

use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;

/**
 * Interface for chain-route-generator.
 */
interface ChainRouteGeneratorInterface
{
    /**
     * Using configuration for entity to generate a route.
     *
     * @param RoutableInterface $entity
     * @param string $path
     *
     * @return RouteInterface
     */
    public function generate(RoutableInterface $entity, $path = null);
}
