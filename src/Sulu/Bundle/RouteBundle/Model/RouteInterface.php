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

use Doctrine\Common\Collections\Collection;

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
     * @return RouteInterface
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
     * @return RouteInterface
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
     * @return RouteInterface
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
     * @return RouteInterface
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
     * @return RouteInterface|null
     */
    public function getTarget();

    /**
     * Set target.
     *
     * @param RouteInterface $target
     *
     * @return RouteInterface
     */
    public function setTarget(?self $target = null);

    /**
     * Remove target.
     *
     * @return RouteInterface
     */
    public function removeTarget();

    /**
     * Get histories.
     *
     * @return Collection<int, RouteInterface>
     */
    public function getHistories();

    /**
     * Add now history.
     *
     * @return RouteInterface
     */
    public function addHistory(self $history);
}
