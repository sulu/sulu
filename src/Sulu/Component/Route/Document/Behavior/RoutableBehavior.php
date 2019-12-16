<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Route\Document\Behavior;

use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

interface RoutableBehavior extends RoutableInterface, UuidBehavior, LocaleBehavior, StructureBehavior
{
    /**
     * Returns route-path.
     *
     * @return string
     */
    public function getRoutePath();

    /**
     * Remove route.
     */
    public function removeRoute();

    /**
     * Set route-path.
     *
     * @param string $routePath
     */
    public function setRoutePath($routePath);

    /**
     * Set uuid.
     *
     * @param string $uuid
     */
    public function setUuid($uuid);

    /**
     * Returns class of document without proxies.
     *
     * @return string
     */
    public function getClass();
}
