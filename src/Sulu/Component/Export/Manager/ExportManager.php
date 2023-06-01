<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Export\Manager;

use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;

/**
 * Content Export Manager to export languages from Webspace.
 */
class ExportManager implements ExportManagerInterface
{
    /**
     * @var array
     */
    protected $contentTypeOptions = [];

    /**
     * @var ContentTypeManagerInterface
     */
    protected $contentTypeManager;

    public function __construct(ContentTypeManagerInterface $contentTypeManager)
    {
        $this->contentTypeManager = $contentTypeManager;
    }

    public function add($contentTypeName, $format, $options)
    {
        if (!isset($this->contentTypeOptions[$contentTypeName])) {
            $this->contentTypeOptions[$contentTypeName] = [];
        }

        $this->contentTypeOptions[$contentTypeName][$format] = $options;
    }

    public function export($contentTypeName, $propertyValue)
    {
        $contentType = $this->contentTypeManager->get($contentTypeName);

        if ($contentType instanceof ContentTypeExportInterface) {
            return $contentType->exportData($propertyValue);
        }

        return '';
    }

    public function hasExport($contentTypeName, $format)
    {
        return $this->existOptions($contentTypeName, $format);
    }

    public function getOptions($contentTypeName, $format)
    {
        $options = null;

        if ($this->existOptions($contentTypeName, $format)) {
            $options = $this->contentTypeOptions[$contentTypeName][$format];
        }

        return $options;
    }

    /**
     * @param string $contentTypeName
     * @param string $format
     *
     * @return bool
     */
    protected function existOptions($contentTypeName, $format)
    {
        if (
            isset($this->contentTypeOptions[$contentTypeName])
            && isset($this->contentTypeOptions[$contentTypeName][$format])
        ) {
            return true;
        }

        return false;
    }
}
