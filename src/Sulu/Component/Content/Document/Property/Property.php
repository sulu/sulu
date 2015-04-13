<?php

namespace Sulu\Component\Content\Document\Property;

use Sulu\Component\Content\Structure\PropertyInterface as StructurePropertyInterface;

/**
 * Value object for content type rendering.
 *
 * TODO: Should probably be removed.
 * TODO: Remove the structure property from this class. It should be passed as an argument
 *       to the content types.
 */
class Property implements PropertyInterface
{
    private $value;
    private $name;
    private $structureProperty;
    private $child;

    public function __construct($name, $document)
    {
        $this->name = $name;
        $this->document = $document;
    }

    public function setStructureProperty(StructurePropertyInterface $structureProperty)
    {
        $this->structureProperty = $structureProperty;
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

    /**
     * {@inheritDoc}
     */
    public function getDocument() 
    {
        return $this->document;
    }

    /**
     * {@inheritDoc}
     */
    public function getStructureProperty()
    {
        return $this->structureProperty;
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
