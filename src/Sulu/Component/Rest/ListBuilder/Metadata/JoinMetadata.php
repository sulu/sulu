<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata;

/**
 * Container for join-metadata.
 */
class JoinMetadata
{
    public const JOIN_METHOD_LEFT = 'LEFT';

    public const JOIN_METHOD_INNER = 'INNER';

    public const JOIN_CONDITION_METHOD_ON = 'ON';

    public const JOIN_CONDITION_METHOD_WITH = 'WITH';

    /**
     * @var string
     */
    private $entityName;

    /**
     * @var string|null
     */
    private $entityField = null;

    /**
     * @var string|null
     */
    private $condition = null;

    /**
     * @var 'ON'|'WITH'
     */
    private $conditionMethod = self::JOIN_CONDITION_METHOD_WITH;

    /**
     * Defines the join method (left, right or inner join).
     *
     * @var 'LEFT'|'INNER'|'RIGHT'
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
     *
     * @return void
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * The field, which should be joined.
     *
     * @return string|null
     */
    public function getEntityField()
    {
        return $this->entityField;
    }

    /**
     * @param string $entityField
     *
     * @return void
     */
    public function setEntityField($entityField)
    {
        $this->entityField = $entityField;
    }

    /**
     * The additional condition which should apply to the join.
     *
     * @return string|null
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param string $condition
     *
     * @return void
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;
    }

    /**
     * @return 'ON'|'WITH'
     */
    public function getConditionMethod()
    {
        return $this->conditionMethod;
    }

    /**
     * @param 'ON'|'WITH' $conditionMethod
     *
     * @return void
     */
    public function setConditionMethod($conditionMethod)
    {
        $this->conditionMethod = $conditionMethod;
    }

    /**
     * The method for the condition to apply (on or with).
     *
     * @return 'LEFT'|'INNER'|'RIGHT'
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param 'LEFT'|'INNER'|'RIGHT' $method
     *
     * @return void
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }
}
