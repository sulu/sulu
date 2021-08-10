<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
    public const OPERATOR_EQUAL = 'eq';

    public const OPERATOR_NOT_EQUAL = 'neq';

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

    public function getValue()
    {
        return $this->value;
    }

    public function jsonSerialize()
    {
        return [
            'property' => $this->property,
            'operator' => $this->operator,
            'value' => $this->value,
        ];
    }
}
