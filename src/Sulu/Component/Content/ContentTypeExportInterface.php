<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\PropertyInterface;

/**
 * Interface for exportable Content Types.
 */
interface ContentTypeExportInterface
{
    /**
     * @param mixed $propertyValue
     *
     * @return string
     */
    public function exportData($propertyValue);

    /**
     * @param NodeInterface $node
     * @param PropertyInterface $property
     * @param mixed $value
     * @param int $userId
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     */
    public function importData(
        NodeInterface $node,
        PropertyInterface $property,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    );
}
