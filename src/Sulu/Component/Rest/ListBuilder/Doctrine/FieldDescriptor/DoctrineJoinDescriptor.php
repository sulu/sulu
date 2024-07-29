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
     * @param string $join the field, which should be joined
     * @param string $joinCondition the additional condition which should apply to the join
     * @param 'LEFT'|'INNER'|'RIGHT' $joinMethod
     * @param 'ON'|'WITH' $joinConditionMethod
     */
    public function __construct(
        private string $entityName,
        private ?string $join = null,
        private ?string $joinCondition = null,
        private string $joinMethod = self::JOIN_METHOD_LEFT,
        private string $joinConditionMethod = self::JOIN_CONDITION_METHOD_WITH
    ) {
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
