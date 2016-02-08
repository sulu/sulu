<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata;

/**
 * Container for virtual-property-metadata.
 */
class VirtualPropertyMetadata extends PropertyMetadata
{
    public function __construct($class, $name)
    {
        $this->class = $class;
        $this->name = $name;
    }
}
