<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Entity;

/**
 * Interface FilterRepositoryInterface
 * @package Sulu\Bundle\ResourceBundle\Entity
 */
interface FilterRepositoryInterface {

    /**
     * Searches for a filter by id and locale
     *
     * @param $id
     * @param $locale
     * @return mixed
     */
    public function findByIdAndLocale($id, $locale);

    /**
     * Searches for all filters by locale
     *
     * @param $locale
     * @return mixed
     */
    public function findAllByLocale($locale);

    /**
     * Searches for a filter by id
     *
     * @param $id
     * @return mixed
     */
    public function findById($id);
}
