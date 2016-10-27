<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Navigation;

/**
 * This class represents a condition for the content-navigation item.
 */
class DisplayCondition implements \JsonSerializable
{
    const OPERATOR_EQUAL = 'eq';
    const OPERATOR_NOT_EQUAL = 'neq';

    /**
     * @var string
     */
    private $property;

    /**
     * @var string
     */
    private $operator;

    /**
     * @var mixed
     */
    private $value;

    public function __construct($property, $operator, $value)
    {
        $this->property = $property;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'property' => $this->property,
            'operator' => $this->operator,
            'value' => $this->value,
        ];
    }
}
