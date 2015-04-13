<?php

namespace Sulu\Component\Content\Document\Property;

use Sulu\Component\Content\Compat\Structure\PropertyInterface as StructurePropertyInterface;

/**
 * Value object for content type rendering.
 */
class PropertyValue
{
    private $value;
    private $name;

    public function __construct($name, $value = null)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
