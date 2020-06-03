<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata\Doctrine;

use Metadata\PropertyMetadata as BasePropertyMetadata;

/**
 * Container for property-metadata.
 */
class PropertyMetadata extends BasePropertyMetadata
{
    /**
     * @var mixed
     */
    private $type;

    public function __construct($class, $name, $type)
    {
        $this->class = $class;
        $this->name = $name;
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function serialize()
    {
        return \serialize(
            [
                $this->class,
                $this->name,
                $this->type,
            ]
        );
    }

    public function unserialize($str)
    {
        list(
            $this->class,
            $this->name,
            $this->type) = \unserialize($str);
    }
}
