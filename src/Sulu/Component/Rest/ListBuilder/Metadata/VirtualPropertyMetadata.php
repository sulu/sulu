<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata;

class VirtualPropertyMetadata extends PropertyMetadata
{
    public function __construct($class, $name)
    {
        $this->class = $class;
        $this->name = $name;
    }
}
