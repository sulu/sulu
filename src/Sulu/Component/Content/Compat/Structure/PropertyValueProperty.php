<?php

namespace Sulu\Component\Content\Compat\Structure;

use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Document\Property\PropertyValue;

/**
 * This property wraps the "new" PropertyValue class
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
