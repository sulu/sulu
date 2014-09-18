<?php

namespace Sulu\Component\Content;

/**
 * Structure tag class, used to add arbitary
 * information to allow decoupled mapping for other
 * libraries/bundles.
 */
class StructureTag
{
    protected $name;
    protected $attributes;

    /**
     * @param string $name Name of tag
     * @param array $attributes Tag attributes
     */
    public function __construct($name, $attributes)
    {
        $this->name = $name;
        $this->attributes = $attributes;
    }

    /**
     * @return string
     */
    public function getName() 
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getAttributes() 
    {
        return $this->attributes;
    }
}
