<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Import;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\PropertyInterface;

/**
 * Defines the methods for the ContentImportManager.
 */
interface ContentImportManagerInterface
{
    /**
     * Import property of a document.
     *
     * @param $contentTypeName
     * @param NodeInterface $node
     * @param PropertyInterface $property
     * @param int $userId
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     */
    public function import(
        $contentTypeName,
        NodeInterface $node,
        PropertyInterface $property,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    );

    /**
     * Check can import type by given typename.
     *
     * @param $contentTypeName
     * @param $format
     *
     * @return bool
     */
    public function hasImport($contentTypeName, $format);
}
