<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Export;

use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;

/**
 * Content Export Manager to export languages from Webspace.
 */
class ContentExportManager implements ContentExportManagerInterface
{
    /**
     * @var array
     */
    protected $contentTypeOptions = [];

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
    public function add($contentTypeName, $format, $options)
    {
        if (!isset($this->contentTypeOptions[$contentTypeName])) {
            $this->contentTypeOptions[$contentTypeName] = [];
        }

        $this->contentTypeOptions[$contentTypeName][$format] = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function export($contentTypeName, $propertyValue)
    {
        $contentType = $this->contentTypeManager->get($contentTypeName);

        if ($contentType instanceof ContentTypeExportInterface) {
            return $contentType->exportData($propertyValue);
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function hasExport($contentTypeName, $format)
    {
        return $this->existOptions($contentTypeName, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions($contentTypeName, $format)
    {
        $options = null;

        if ($this->existOptions($contentTypeName, $format)) {
            $options = $this->contentTypeOptions[$contentTypeName][$format];
        }

        return $options;
    }

    /**
     * @param $contentTypeName
     * @param $format
     *
     * @return bool
     */
    protected function existOptions($contentTypeName, $format)
    {
        if (
            isset($this->contentTypeOptions[$contentTypeName]) &&
            isset($this->contentTypeOptions[$contentTypeName][$format])
        ) {
            return true;
        }

        return false;
    }
}
