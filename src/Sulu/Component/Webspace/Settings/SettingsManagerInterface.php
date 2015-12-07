<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * @param mixed $data
     */
    public function save($webspaceKey, $key, $data);

    /**
     * Load webspace settings value.
     *
     * @param string $webspaceKey
     * @param string $key
     *
     * @return mixed
     */
    public function load($webspaceKey, $key);
}
