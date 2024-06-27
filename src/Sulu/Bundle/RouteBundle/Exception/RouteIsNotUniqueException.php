<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
    public function __construct(
        private RouteInterface $route,
        private RoutableInterface $entity,
    ) {
        parent::__construct(
            \sprintf('Route "%s" is not unique "%s"(%s)', $this->route->getPath(), \get_class($this->entity), $this->entity->getId())
        );
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
