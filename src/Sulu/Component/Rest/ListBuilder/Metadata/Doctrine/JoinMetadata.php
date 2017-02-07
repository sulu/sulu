<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata\Doctrine;

/**
 * Container for join-metadata.
 */
class JoinMetadata
{
    const JOIN_METHOD_LEFT = 'LEFT';
    const JOIN_METHOD_INNER = 'INNER';

    const JOIN_CONDITION_METHOD_ON = 'ON';
    const JOIN_CONDITION_METHOD_WITH = 'WITH';

    /**
     * @var string
     */
    private $entityName;

    /**
     * @var string
     */
    private $entityField;

    /**
     * @var string
     */
    private $condition = null;

    /**
     * @var string
     */
    private $conditionMethod = self::JOIN_CONDITION_METHOD_WITH;

    /**
     * Defines the join method (left, right or inner join).
     *
     * @var string
     */
    private $method = self::JOIN_METHOD_LEFT;

    /**
     * The name of the entity to join.
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @param string $entityName
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * The field, which should be joined.
     *
     * @return string
     */
    public function getEntityField()
    {
        return $this->entityField;
    }

    /**
     * @param string $entityField
     */
    public function setEntityField($entityField)
    {
        $this->entityField = $entityField;
    }

    /**
     * The additional condition which should apply to the join.
     *
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param string $condition
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;
    }

    /**
     * @return string
     */
    public function getConditionMethod()
    {
        return $this->conditionMethod;
    }

    /**
     * @param string $conditionMethod
     */
    public function setConditionMethod($conditionMethod)
    {
        $this->conditionMethod = $conditionMethod;
    }

    /**
     * The method for the condition to apply (on or with).
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }
}
