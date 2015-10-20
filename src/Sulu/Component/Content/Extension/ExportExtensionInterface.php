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

interface ExportExtensionInterface
{
    /**
     * @param mixed $properties
     * @param string $format
     *
     * @return string[]
     */
    public function export($properties, $format = null);
}
