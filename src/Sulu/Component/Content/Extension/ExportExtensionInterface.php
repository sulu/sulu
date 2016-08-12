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

use PHPCR\NodeInterface;

interface ExportExtensionInterface
{
    /**
     * @param mixed $properties
     * @param string $format
     *
     * @return string[]
     */
    public function export($properties, $format = null);

    /**
     * @return string[]
     */
    public function getImportPropertyNames();

    /**
     * @param NodeInterface $node
     * @param array $data
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $format
     */
    public function import(NodeInterface $node, $data, $webspaceKey, $languageCode, $format);
}
