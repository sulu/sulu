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
     */
    public function hasDatasource(): bool;

    /**
     * Returns resourceKey for datasource.
     * If NULL no datasource will be displayed.
     */
    public function getDatasourceResourceKey(): ?string;

    public function getDatasourceListKey(): ?string;

    /**
     * Returns the adapter to be used for the datasource.
     */
    public function getDatasourceAdapter(): ?string;

    /**
     * Returns true if the provider can handle audience targeting.
     */
    public function hasAudienceTargeting(): bool;

    /**
     * Sets whether or not the audience targeting feature is activated.
     */
    public function setAudienceTargeting(bool $audienceTargeting);

    /**
     * Returns TRUE if tags should be displayed.
     */
    public function hasTags(): bool;

    /**
     * Returns TRUE if categories should be displayed.
     */
    public function hasCategories(): bool;

    /**
     * Returns TRUE if sorting should be displayed.
     */
    public function hasSorting(): bool;

    /**
     * Returns items for sorting select.
     *
     * @return PropertyParameter[]
     */
    public function getSorting(): ?array;

    /**
     * Returns TRUE if limit should be displayed.
     */
    public function hasLimit(): bool;

    /**
     * Returns TRUE if present as should be displayed.
     */
    public function hasPresentAs(): bool;

    /**
     * Indicates pagination is possible.
     */
    public function hasPagination(): bool;
}
