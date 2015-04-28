<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Filter;

use Sulu\Bundle\ResourceBundle\Api\Filter;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;

/**
 * Interface FilterManagerInterface
 * @package Sulu\Bundle\ResourceBundle\Filter
 */
interface FilterManagerInterface
{
    /**
     * Returns an array of field descriptors
     *
     * @param $locale
     * @return DoctrineFieldDescriptor[]
     */
    public function getFieldDescriptors($locale);

    /**
     * Finds a filter by id and locale
     *
     * @param integer $id
     * @param string $locale
     * @return Filter
     */
    public function findByIdAndLocale($id, $locale);

    /**
     * Finds all filters by locale
     *
     * @param string $locale
     * @return Filter[]
     */
    public function findAllByLocale($locale);

    /**
     * Removes a filter with the given id
     *
     * @param $id
     */
    public function delete($id);

    /**
     * Saves the given filter
     *
     * @param array $data
     * @param string $locale
     * @param integer $userId
     * @param integer $id
     * @return Filter
     */
    public function save(array $data, $locale, $userId, $id = null);
}
