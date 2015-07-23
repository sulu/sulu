<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Configuration;

/**
 * Provides configuration for smart-content.
 */
interface ProviderConfigurationInterface
{
    /**
     * Returns configuration for datasource.
     * If NULL no datasource will be displayed.
     *
     * @return null|ComponentConfigurationInterface
     */
    public function getDatasource();

    /**
     * Indicates filter by tags is possible.
     * If FALSE no tags will be displayed.
     *
     * @return boolean
     */
    public function getTags();

    /**
     * Returns configuration for categories.
     * If NULL no categories will be displayed.
     *
     * @return null|CategoriesConfigurationInterface
     */
    public function getCategories();

    /**
     * Returns items for sorting select.
     * If NULL no sorting select will be displayed.
     *
     * @return null|KeyTitlePairInterface[]
     */
    public function getSorting();

    /**
     * Indicates limitation is possible.
     * If FALSE no tags will be displayed.
     *
     * @return boolean
     */
    public function getLimit();

    /**
     * Returns items for present as select.
     * If NULL no present as select will be displayed.
     *
     * @return KeyTitlePairInterface
     */
    public function getPresentAs();

    /**
     * Indicates pagination is possible.
     *
     * @return boolean
     */
    public function getPaginated();
}
