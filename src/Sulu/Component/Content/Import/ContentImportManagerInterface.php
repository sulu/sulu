<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Import;

use PHPCR\NodeInterface;

/**
 * Defines the methods for the ContentImportManager.
 */
interface ContentImportManagerInterface
{
    /**
     * @param $contentTypeName
     * @param NodeInterface $node
     * @param string $name
     * @param string|array $value
     * @param integer $userId
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     */
    public function import(
        $contentTypeName,
        NodeInterface $node,
        $name,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    );

    /**
     * @param $contentTypeName
     * @param $format
     *
     * @return bool
     */
    public function hasImport($contentTypeName, $format);
}
