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
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Import\Exception\ContentTypeImportMissingException;

/**
 * Import content by given xliff file from ContentExportManager.
 */
class ContentImportManager implements ContentImportManagerInterface
{
    /**
     * @var ContentTypeManagerInterface
     */
    protected $contentTypeManager;

    /**
     * @param ContentTypeManagerInterface $contentTypeManager
     */
    public function __construct(ContentTypeManagerInterface $contentTypeManager)
    {
        $this->contentTypeManager = $contentTypeManager;
    }

    /**
     * {@inheritdoc}
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
    ) {
        $contentType = $this->contentTypeManager->get($contentTypeName);

        if (!$contentType instanceof ContentTypeExportInterface) {
            throw new ContentTypeImportMissingException($contentTypeName);
        }

        $contentType->importData($node, $property, $value, $userId, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
    public function hasImport($contentTypeName, $format)
    {
        $contentType = $this->contentTypeManager->get($contentTypeName);

        if ($contentType instanceof ContentTypeExportInterface) {
            return true;
        }

        return false;
    }
}
