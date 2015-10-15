<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Component\Content\Export;

interface ContentExportManagerInterface
{
    /**
     * @param $contentTypeName
     * @param $format
     * @param $options
     */
    public function add($contentTypeName, $format, $options);

    /**
     * @param $contentTypeName
     * @param $propertyValue
     */
    public function export($contentTypeName, $propertyValue);

    /**
     * @param $contentTypeName
     * @param $format
     *
     * @return bool
     */
    public function hasExport($contentTypeName, $format);

    /**
     * @param $contentTypeName
     * @param $format
     *
     * @return array
     */
    public function getOptions($contentTypeName, $format);
}
