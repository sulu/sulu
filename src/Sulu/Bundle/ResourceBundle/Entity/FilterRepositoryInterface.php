<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Entity;

/**
 * The interface for the filter entity repository.
 */
interface FilterRepositoryInterface
{
    /**
     * Searches for a filter by id and locale.
     *
     * @param $id
     * @param $locale
     *
     * @return mixed
     */
    public function findByIdAndLocale($id, $locale);

    /**
     * Searches for a filter by id.
     *
     * @param $id
     *
     * @return mixed
     */
    public function findById($id);

    /**
     * Deletes multiple filters.
     *
     * @param $ids
     *
     * @return mixed
     */
    public function deleteByIds($ids);

    /**
     * Searches for all filters of a user by context and locale
     * includes filters without a user which were e.g. created
     * by fixtures.
     *
     * @param string $locale
     * @param string $context
     * @param string|int $userId
     *
     * @return mixed
     */
    public function findByUserAndContextAndLocale($locale, $context, $userId);
}
