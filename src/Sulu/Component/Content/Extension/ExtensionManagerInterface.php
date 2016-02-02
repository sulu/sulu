<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Extension;

interface ExtensionManagerInterface
{
    /**
     * Returns extensions for structure.
     *
     * @param string $key
     *
     * @return ExtensionInterface[]
     */
    public function getExtensions($key);

    /**
     * Indicates that the structure has a extension.
     *
     * @param string $key
     * @param string $name
     *
     * @return bool
     */
    public function hasExtension($key, $name);

    /**
     * Returns a extension.
     *
     * @param string $key
     * @param string $name
     *
     * @return ExtensionInterface
     */
    public function getExtension($key, $name);
}
