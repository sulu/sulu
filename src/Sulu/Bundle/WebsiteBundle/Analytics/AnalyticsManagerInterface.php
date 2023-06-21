<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Analytics;

use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsInterface;

/**
 * Manages analytics.
 */
interface AnalyticsManagerInterface
{
    /**
     * Returns all analytics for given webspace and environment.
     *
     * @param string $webspaceKey
     *
     * @return AnalyticsInterface[]
     */
    public function findAll($webspaceKey);

    /**
     * Returns key by id.
     *
     * @param int $id
     *
     * @return AnalyticsInterface
     */
    public function find($id);

    /**
     * Create new key for given webspace.
     *
     * @param string $webspaceKey
     * @param array $data
     *
     * @return AnalyticsInterface
     */
    public function create($webspaceKey, $data);

    /**
     * Updates key with given id.
     *
     * @param int $id
     * @param array $data
     *
     * @return AnalyticsInterface
     */
    public function update($id, $data);

    /**
     * Removes key with given id.
     *
     * @param int $id
     */
    public function remove($id);

    /**
     * Removes key with given id.
     *
     * @param int[] $ids
     */
    public function removeMultiple(array $ids);
}
