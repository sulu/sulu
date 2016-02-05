<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata\Doctrine;

use Metadata\PropertyMetadata as BasePropertyMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\PropertyType;

class PropertyMetadata extends BasePropertyMetadata
{
    /**
     * @var PropertyType
     */
    private $type;

    public function __construct($class, $name, PropertyType $type)
    {
        $this->class = $class;
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * @return PropertyType
     */
    public function getType()
    {
        return $this->type;
    }
}
