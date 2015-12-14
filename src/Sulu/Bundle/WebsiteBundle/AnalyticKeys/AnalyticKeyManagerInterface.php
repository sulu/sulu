<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\AnalyticKeys;

use Sulu\Bundle\WebsiteBundle\Entity\AnalyticKey;

/**
 * Manages analytic keys.
 */
interface AnalyticKeyManagerInterface
{
    /**
     * Returns all keys for given repository.
     *
     * @param string $webspaceKey
     *
     * @return AnalyticKey[]
     */
    public function findAll($webspaceKey);

    /**
     * Returns key by id.
     *
     * @param int $id
     *
     * @return AnalyticKey
     */
    public function find($id);

    /**
     * Create new key for given webspace.
     *
     * @param string $webspaceKey
     * @param array $data
     *
     * @return AnalyticKey
     */
    public function create($webspaceKey, $data);

    /**
     * Updates key with given id.
     *
     * @param int $id
     * @param array $data
     *
     * @return AnalyticKey
     */
    public function update($id, $data);

    /**
     * Removes key with given id.
     *
     * @param int $id
     */
    public function remove($id);
}
