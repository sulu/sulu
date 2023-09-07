<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Settings;

/**
 * Manages settings on top the webspace node.
 */
interface SettingsManagerInterface
{
    /**
     * Save webspace settings value.
     *
     * @param string $webspaceKey
     * @param string $key
     */
    public function save($webspaceKey, $key, $data);

    /**
     * Remove webspace settings value.
     *
     * @param string $webspaceKey
     * @param string $key
     */
    public function remove($webspaceKey, $key);

    /**
     * Load webspace settings value.
     *
     * @param string $webspaceKey
     * @param string $key
     */
    public function load($webspaceKey, $key);

    /**
     * Load webspace settings value and return it as a string.
     *
     * @param string $webspaceKey
     * @param string $key
     *
     * @return string|string[]|null
     */
    public function loadString($webspaceKey, $key);

    /**
     * Load webspace settings by wildcard.
     *
     * @param string $webspaceKey
     * @param string $wildcard
     *
     * @return array<string, string|string[]>
     */
    public function loadByWildcard($webspaceKey, $wildcard);

    /**
     * Load webspace settings as strings by wildcard.
     *
     * @param string $webspaceKey
     * @param string $wildcard
     *
     * @return array<string, string|string[]>
     */
    public function loadStringByWildcard($webspaceKey, $wildcard);
}
