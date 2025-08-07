<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Model;

/**
 * This interface indicates a routable model.
 */
interface RoutableInterface
{
    /**
     * Returns identifier.
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
     * @return void
     */
    public function setRoute(RouteInterface $route);

    /**
     * @return string
     */
    public function getLocale();
}
