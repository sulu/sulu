<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Resource;

use Sulu\Bundle\ResourceBundle\Api\Filter;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;

/**
 * Interface FilterManagerInterface.
 */
interface FilterManagerInterface
{
    /**
     * Returns an array of field descriptors.
     *
     * @param $locale
     *
     * @return DoctrineFieldDescriptor[]
     */
    public function getFieldDescriptors($locale);

    /**
     * Returns an array of field descriptors specific for the list.
     *
     * @param $locale
     *
     * @return DoctrineFieldDescriptor[]
     */
    public function getListFieldDescriptors($locale);

    /**
     * Finds a filter by id and locale.
     *
     * @param int $id
     * @param string $locale
     *
     * @return Filter
     */
    public function findByIdAndLocale($id, $locale);

    /**
     * Finds all filters filtered by context and user and
     * for the given locale.
     *
     * @param string $context
     * @param $userId
     * @param string $locale
     *
     * @return \Sulu\Bundle\ResourceBundle\Api\Filter[]
     */
    public function findFiltersForUserAndContext($context, $userId, $locale);

    /**
     * Removes a filter with the given id.
     *
     * @param $id
     */
    public function delete($id);

    /**
     * Saves the given filter.
     *
     * @param array $data
     * @param string $locale
     * @param int $userId
     * @param int $id
     *
     * @return Filter
     */
    public function save(array $data, $locale, $userId, $id = null);

    /**
     * Deletes multiple filters at once.
     *
     * @param $ids
     */
    public function batchDelete($ids);

    /**
     * Returns the configured features for a context.
     *
     * @param $context
     *
     * @return array|null
     */
    public function getFeaturesForContext($context);

    /**
     * Checks if the context exists.
     *
     * @param $context
     *
     * @return bool
     */
    public function hasContext($context);

    /**
     * Checks if a feature is enabled for a context.
     *
     * @param $context
     * @param $feature
     *
     * @return bool
     */
    public function isFeatureEnabled($context, $feature);
}
