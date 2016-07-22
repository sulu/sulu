<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Entity;

use Sulu\Bundle\RouteBundle\Model\RouteInterface;

/**
 * Contains special queries to find routes.
 */
interface RouteRepositoryInterface
{
    /**
     * Returns new route entity.
     *
     * @return RouteInterface
     */
    public function createNew();

    /**
     * Returns route-entity by route.
     *
     * @param string $path
     * @param string $locale
     *
     * @return RouteInterface
     */
    public function findByPath($path, $locale);

    /**
     * Returns route-entity by id.
     *
     * @param int $id
     *
     * @return RouteInterface
     */
    public function find($id);
}
