<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

/**
 * Structure tag class, used to add arbitary
 * information to allow decoupled mapping for other
 * libraries/bundles.
 */
class StructureTag
{
    /**
     * The name of the structure tag.
     *
     * @var string
     */
    protected $name;

    /**
     * An array of attributes for this specific tag.
     *
     * @var array
     */
    protected $attributes;

    /**
     * @param string $name       Name of tag
     * @param array  $attributes Tag attributes
     */
    public function __construct($name, $attributes)
    {
        $this->name = $name;
        $this->attributes = $attributes;
    }

    /**
     * Returns the name of the structure tag.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the attributes for the specific tags.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}
