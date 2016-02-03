<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata\General;

use Metadata\PropertyMetadata as BasePropertyMetadata;

class ConcatenationPropertyMetadata extends PropertyMetadata
{
    /**
     * @var BasePropertyMetadata[]
     */
    private $properties = [];

    public function __construct($class, $name)
    {
        $this->class = $class;
        $this->name = $name;

        // default for translation can be overwritten by setter
        $this->setTranslation(ucfirst($name));
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($obj, $value)
    {
        throw new \LogicException('Property is immutable.');
    }

    /**
     * @return BasePropertyMetadata[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param BasePropertyMetadata[] $properties
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
    }

    public function addPropertyMetadata(BasePropertyMetadata $property)
    {
        $this->properties[] = $property;
    }
}
