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

/**
 * Interface for Content Export Manager.
 */
interface ContentExportManagerInterface
{
    /**
     * Add ContentType and the format.
     * This will be set on the Content-Type Service.
     *
     * @param $contentTypeName
     * @param $format
     * @param $options
     */
    public function add($contentTypeName, $format, $options);

    /**
     * Export data for document by given Content-Type.
     *
     * @param $contentTypeName
     * @param $propertyValue
     */
    public function export($contentTypeName, $propertyValue);

    /**
     * Checks the content-type if this has an export.
     *
     * @param $contentTypeName
     * @param $format
     *
     * @return bool
     */
    public function hasExport($contentTypeName, $format);

    /**
     * Returns the export options by the given content-type.
     *
     * @param $contentTypeName
     * @param $format
     *
     * @return array
     */
    public function getOptions($contentTypeName, $format);
}
