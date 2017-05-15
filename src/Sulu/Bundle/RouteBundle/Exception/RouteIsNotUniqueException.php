<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Exception;

use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;

/**
 * Route is not unique exception.
 */
class RouteIsNotUniqueException extends \DomainException
{
    /**
     * @var RouteInterface
     */
    private $route;

    /**
     * @var RoutableInterface
     */
    private $entity;

    /**
     * @param RouteInterface $route
     * @param RoutableInterface $entity
     */
    public function __construct(RouteInterface $route, RoutableInterface $entity)
    {
        $this->route = $route;
        $this->entity = $entity;
    }

    /**
     * Returns route.
     *
     * @return RouteInterface
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Returns entity.
     *
     * @return RoutableInterface
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
