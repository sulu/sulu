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

    /**
     * @var array
     */
    private $parameters = [];

    public function __construct($class, $name, $inputType, array $parameters)
    {
        $this->class = $class;
        $this->name = $name;
        $this->inputType = $inputType;
        $this->parameters = $parameters;
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
     * Returns name of property.
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns parameter for input.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
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
                $this->parameters,
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
            $this->parameters,
            ) = unserialize($str);
    }
}
