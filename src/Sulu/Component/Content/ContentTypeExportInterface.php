<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

/**
 * Interface ContentTypeExportInterface
 * @package Sulu\Component\Content
 */
interface ContentTypeExportInterface
{
    /**
     * @param mixed $propertyValue
     * @return string
     */
    public function exportData($propertyValue);
}
