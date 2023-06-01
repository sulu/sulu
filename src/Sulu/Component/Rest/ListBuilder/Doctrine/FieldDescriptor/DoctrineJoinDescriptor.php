<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor;

use Sulu\Component\Rest\ListBuilder\Doctrine\EncodeAliasTrait;

/**
 * This class describes a doctrine join.
 */
class DoctrineJoinDescriptor
{
    use EncodeAliasTrait;

    public const JOIN_METHOD_LEFT = 'LEFT';

    public const JOIN_METHOD_INNER = 'INNER';

    public const JOIN_CONDITION_METHOD_ON = 'ON';

    public const JOIN_CONDITION_METHOD_WITH = 'WITH';

    /**
     * The name of the entity to join.
     *
     * @var string
     */
    private $entityName;

    /**
     * The field, which should be joined.
     *
     * @var string
     */
    private $join;

    /**
     * The additional condition which should apply to the join.
     *
     * @var string
     */
    private $joinCondition;

    /**
     * The method for the condition to apply.
     *
     * @var 'ON'|'WITH'
     */
    private $joinConditionMethod;

    /**
     * Defines the join method (left, right or inner join).
     *
     * @var 'LEFT'|'INNER'|'RIGHT'
     */
    private $joinMethod;

    /**
     * @param 'LEFT'|'INNER'|'RIGHT' $joinMethod
     * @param 'ON'|'WITH' $joinConditionMethod
     */
    public function __construct(
        string $entityName,
        ?string $join = null,
        ?string $joinCondition = null,
        string $joinMethod = self::JOIN_METHOD_LEFT,
        string $joinConditionMethod = self::JOIN_CONDITION_METHOD_WITH
    ) {
        $this->entityName = $entityName;
        $this->join = $join;
        $this->joinCondition = $joinCondition;
        $this->joinConditionMethod = $joinConditionMethod;
        $this->joinMethod = $joinMethod;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @return string
     */
    public function getJoin()
    {
        // When joining without a relation the join should not be encoded
        if (null === $this->join || $this->entityName === $this->join) {
            return $this->entityName;
        }

        if (false === \strpos($this->join, '.')) {
            return $this->join;
        }

        return $this->encodeAlias($this->join);
    }

    /**
     * @return string
     */
    public function getJoinCondition()
    {
        return $this->encodeAlias($this->joinCondition);
    }

    /**
     * @return 'ON'|'WITH'
     */
    public function getJoinConditionMethod()
    {
        return $this->joinConditionMethod;
    }

    /**
     * @return 'LEFT'|'INNER'|'RIGHT'
     */
    public function getJoinMethod()
    {
        return $this->joinMethod;
    }
}
