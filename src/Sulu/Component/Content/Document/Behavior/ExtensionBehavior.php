<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Behavior;

/**
 * Documents implementing this behavior can have extensions applied to their
 * content.
 */
interface ExtensionBehavior extends StructureBehavior
{
    /**
     * Returns all extension data.
     *
     * @return array
     */
    public function getExtensionsData();

    /**
     * Set all the extension data.
     *
     * @param array $extensionData
     */
    public function setExtensionsData($extensionData);

    /**
     * Set data for a specific extension.
     *
     * @param string $name Extension name
     * @param array  $data Extension data
     */
    public function setExtension($name, $data);
}
