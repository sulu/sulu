<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Configuration;

/**
 * Builder with fluent interface for smart content configuration.
 */
interface BuilderInterface
{
    public static function create();

    /**
     * Enables tags.
     *
     * @param bool|true $enable
     *
     * @return BuilderInterface
     */
    public function enableTags(bool $enable = true);

    /**
     * Enables categories.
     *
     * @param bool|true $enable
     *
     * @return BuilderInterface
     */
    public function enableCategories(bool $enable = true);

    /**
     * Enables limit.
     *
     * @param bool|true $enable
     *
     * @return BuilderInterface
     */
    public function enableLimit(bool $enable = true);

    /**
     * Enables pagination.
     *
     * @param bool|true $enable
     *
     * @return BuilderInterface
     */
    public function enablePagination(bool $enable = true);

    /**
     * Enables present as.
     *
     * @param bool|true $enable
     *
     * @return BuilderInterface
     */
    public function enablePresentAs(bool $enable = true);

    /**
     * Enables datasource.
     *
     * @return BuilderInterface
     */
    public function enableDatasource(string $resourceKey, string $listKey, string $adapter);

    /**
     * Enables audience targeting.
     *
     * @param bool $enable
     *
     * @return BuilderInterface
     */
    public function enableAudienceTargeting(bool $enable = true);

    /**
     * Enables categories.
     *
     * @param array $sorting array of arrays with keys column and title (translation key)
     *
     * @return BuilderInterface
     */
    public function enableSorting(array $sorting);

    /**
     * Returns build configuration.
     */
    public function getConfiguration(): ProviderConfigurationInterface;
}
