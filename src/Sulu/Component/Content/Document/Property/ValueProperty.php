<?php

namespace Sulu\Component\Content\Document\Property;

use Sulu\Component\Content\Compat\Structure\PropertyInterface as StructurePropertyInterface;

/**
 * Value object for content type rendering.
 *
 * TODO: Should probably be removed.
 */
class ValueProperty implements PropertyInterface
{
    private $value;
    private $children;

    public function __construct($name)
    {
        $this->name = $name;
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

    public function getChildProperties()
    {
        return $this->children;
    }

    public function addChild(Property $property)
    {
        $this->children[] = $property;
    }
}
