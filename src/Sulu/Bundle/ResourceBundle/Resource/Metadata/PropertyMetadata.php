<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Resource\Metadata;

use Metadata\PropertyMetadata as BasePropertyMetadata;

/**
 * Container for property-metadata.
 */
class PropertyMetadata extends BasePropertyMetadata
{
    /**
     * @var string
     */
    private $inputType;

    public function __construct($class, $name, $inputType)
    {
        $this->class = $class;
        $this->name = $name;
        $this->inputType = $inputType;
    }

    /**
     * Returns input-type.
     *
     * @return string
     */
    public function getInputType()
    {
        return $this->inputType;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            [
                $this->class,
                $this->name,
                $this->inputType,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        list(
            $this->class,
            $this->name,
            $this->inputType,
            ) = unserialize($str);
    }
}
