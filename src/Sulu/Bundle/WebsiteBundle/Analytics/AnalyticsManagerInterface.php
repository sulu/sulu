<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Analytics;

use Sulu\Bundle\WebsiteBundle\Entity\Analytics;

/**
 * Manages analytics.
 */
interface AnalyticsManagerInterface
{
    /**
     * Returns all analytics for given webspace.
     *
     * @param string $webspaceKey
     *
     * @return Analytics[]
     */
    public function findAll($webspaceKey);

    /**
     * Returns key by id.
     *
     * @param int $id
     *
     * @return Analytics
     */
    public function find($id);

    /**
     * Create new key for given webspace.
     *
     * @param string $webspaceKey
     * @param array $data
     *
     * @return Analytics
     */
    public function create($webspaceKey, $data);

    /**
     * Updates key with given id.
     *
     * @param int $id
     * @param array $data
     *
     * @return Analytics
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
