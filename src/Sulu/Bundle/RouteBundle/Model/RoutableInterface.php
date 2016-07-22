<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Model;

use Sulu\Bundle\RouteBundle\Entity\Route;

/**
 * This interface indicates a routable model.
 */
interface RoutableInterface
{
    /**
     * Returns identifier.
     *
     * @return mixed
     */
    public function getId();

    /**
     * Returns route.
     *
     * @return RouteInterface
     */
    public function getRoute();

    /**
     * Set route.
     *
     * @param RouteInterface $route
     */
    public function setRoute(RouteInterface $route);

    /**
     * @return string
     */
    public function getLocale();
}
