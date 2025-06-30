<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\SmartContent\Configuration;

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
    public function setAudienceTargeting(bool $audienceTargeting): void;

    /**
     * Returns TRUE if tags should be displayed.
     */
    public function hasTags(): bool;

    /**
     * Returns types for types filtering.
     *
     * @return null|PropertyParameter[]
     */
    public function getTypes(): ?array;

    /**
     * Returns TRUE if types should be displayed.
     */
    public function hasTypes(): bool;

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

    /**
     * Returns the name of the view to navigate to when a smart content item in the UI is clicked.
     */
    public function getView(): ?string;

    /**
     * Returns the mapping from smart content item properties to view.
     *
     * @return array<string, string>|null
     */
    public function getResultToView(): ?array;
}
