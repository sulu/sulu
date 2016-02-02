<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Structure;

use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Document\Structure\PropertyValue;

/**
 * This property wraps the "new" PropertyValue class.
 */
class PropertyValueProperty extends Property
{
    private $propertyValue;

    public function setPropertyValue(PropertyValue $propertyValue)
    {
        $this->propertyValue = $propertyValue;
    }

    public function getValue()
    {
        return $this->propertyValue->getValue();
    }

    public function setValue($value)
    {
        $this->propertyValue->setValue($value);
    }
}
