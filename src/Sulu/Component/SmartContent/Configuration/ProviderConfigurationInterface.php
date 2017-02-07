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

use Sulu\Component\Content\Compat\PropertyParameter;

/**
 * Provides configuration for smart-content.
 */
interface ProviderConfigurationInterface
{
    /**
     * Returns TRUE if datasource should be displayed.
     * Configuration will be returned from 'getDatasource()'.
     *
     * @return bool
     */
    public function hasDatasource();

    /**
     * Returns configuration for datasource.
     * If NULL no datasource will be displayed.
     *
     * @return null|ComponentConfigurationInterface
     */
    public function getDatasource();

    /**
     * Returns TRUE if tags should be displayed.
     *
     * @return bool
     */
    public function hasTags();

    /**
     * Returns TRUE if categories should be displayed.
     *
     * @return bool
     */
    public function hasCategories();

    /**
     * Returns TRUE if sorting should be displayed.
     *
     * @return bool
     */
    public function hasSorting();

    /**
     * Returns items for sorting select.
     *
     * @return PropertyParameter[]
     */
    public function getSorting();

    /**
     * Returns TRUE if limit should be displayed.
     *
     * @return bool
     */
    public function hasLimit();

    /**
     * Returns TRUE if present as should be displayed.
     *
     * @return bool
     */
    public function hasPresentAs();

    /**
     * Indicates pagination is possible.
     *
     * @return bool
     */
    public function hasPagination();

    /**
     * Returns deep-link template.
     *
     * @return string
     */
    public function getDeepLink();
}
