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

use Sulu\Bundle\RouteBundle\Entity\BaseRoute;

/**
 * Represents a concrete route in the route-pool.
 */
interface RouteInterface
{
    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Set route.
     *
     * @param string $path
     *
     * @return BaseRoute
     */
    public function setPath($path);

    /**
     * Get route.
     *
     * @return string
     */
    public function getPath();

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return BaseRoute
     */
    public function setLocale($locale);

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale();

    /**
     * Get entityClass.
     *
     * @return string
     */
    public function getEntityClass();

    /**
     * Set entityClass.
     *
     * @param string $entityClass
     *
     * @return BaseRoute
     */
    public function setEntityClass($entityClass);

    /**
     * Get entityId.
     *
     * @return string
     */
    public function getEntityId();

    /**
     * Set entityId.
     *
     * @param string $entityId
     *
     * @return BaseRoute
     */
    public function setEntityId($entityId);

    /**
     * Get history.
     *
     * @return bool
     */
    public function isHistory();

    /**
     * Set history.
     *
     * @param bool $history
     *
     * @return RouteInterface
     */
    public function setHistory($history);

    /**
     * Get target.
     *
     * @return RouteInterface
     */
    public function getTarget();

    /**
     * Set target.
     *
     * @param RouteInterface $target
     *
     * @return RouteInterface
     */
    public function setTarget(RouteInterface $target);

    /**
     * Remove target.
     *
     * @return RouteInterface
     */
    public function removeTarget();

    /**
     * Get histories.
     *
     * @return RouteInterface[]
     */
    public function getHistories();

    /**
     * Add now history.
     *
     * @param RouteInterface $history
     *
     * @return RouteInterface
     */
    public function addHistory(RouteInterface $history);
}
