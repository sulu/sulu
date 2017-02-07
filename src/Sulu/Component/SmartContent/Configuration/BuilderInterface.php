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
    public function enableTags($enable = true);

    /**
     * Enables categories.
     *
     * @param bool|true $enable
     *
     * @return BuilderInterface
     */
    public function enableCategories($enable = true);

    /**
     * Enables limit.
     *
     * @param bool|true $enable
     *
     * @return BuilderInterface
     */
    public function enableLimit($enable = true);

    /**
     * Enables pagination.
     *
     * @param bool|true $enable
     *
     * @return BuilderInterface
     */
    public function enablePagination($enable = true);

    /**
     * Enables present as.
     *
     * @param bool|true $enable
     *
     * @return BuilderInterface
     */
    public function enablePresentAs($enable = true);

    /**
     * Enables datasource.
     *
     * @param string $component name of component
     * @param array $options options to initialized component
     *
     * @return BuilderInterface
     */
    public function enableDatasource($component, array $options = []);

    /**
     * Enables categories.
     *
     * @param array $sorting array of arrays with keys column and title (translation key)
     *
     * @return BuilderInterface
     */
    public function enableSorting(array $sorting);

    /**
     * Set deep-link.
     *
     * @param string $deepLink
     *
     * @return BuilderInterface
     */
    public function setDeepLink($deepLink);

    /**
     * Returns build configuration.
     *
     * @return ProviderConfigurationInterface
     */
    public function getConfiguration();
}
