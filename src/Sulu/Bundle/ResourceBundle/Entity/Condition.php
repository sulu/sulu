<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Entity;

/**
 * Condition.
 */
class Condition
{
    const TYPE_STRING = 1;
    const TYPE_NUMBER = 2;
    const TYPE_DATETIME = 3;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $operator;

    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $value;

    /**
     * @var int
     */
    private $id;

    /**
     * @var \Sulu\Bundle\ResourceBundle\Entity\ConditionGroup
     */
    private $conditionGroup;

    /**
     * Set field.
     *
     * @param string $field
     *
     * @return Condition
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get field.
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set operator.
     *
     * @param string $operator
     *
     * @return Condition
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Get operator.
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Set type.
     *
     * @param int $type
     *
     * @return Condition
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set value.
     *
     * @param string $value
     *
     * @return Condition
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set conditionGroup.
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\ConditionGroup $conditionGroup
     *
     * @return Condition
     */
    public function setConditionGroup(\Sulu\Bundle\ResourceBundle\Entity\ConditionGroup $conditionGroup)
    {
        $this->conditionGroup = $conditionGroup;

        return $this;
    }

    /**
     * Get conditionGroup.
     *
     * @return \Sulu\Bundle\ResourceBundle\Entity\ConditionGroup
     */
    public function getConditionGroup()
    {
        return $this->conditionGroup;
    }
}
