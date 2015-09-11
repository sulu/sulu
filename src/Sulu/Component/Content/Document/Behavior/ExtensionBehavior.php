<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Behavior;

use Sulu\Component\Content\Document\Extension\ManagedExtensionContainer;

/**
 * Documents implementing this behavior can have extensions applied to their
 * content.
 */
interface ExtensionBehavior extends StructureBehavior
{
    /**
     * Return all extension data.
     *
     * @return array|ManagedExtensionContainer
     */
    public function getExtensionsData();

    /**
     * Set all the extension data.
     *
     * @param array|ManagedExtensionContainer $extensionData
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
