<?php

declare(strict_types=1);

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

    private string $entityName;

    private ?string $entityField = null;

    private ?string $condition = null;

    /**
     * @var 'ON'|'WITH'
     */
    private string $conditionMethod = self::JOIN_CONDITION_METHOD_WITH;

    /**
     * Defines the join method (left, right or inner join).
     *
     * @var 'LEFT'|'INNER'|'RIGHT'
     */
    private string $method = self::JOIN_METHOD_LEFT;

    /**
     * The name of the entity to join.
     */
    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function setEntityName(string $entityName): void
    {
        $this->entityName = $entityName;
    }

    /**
     * The field, which should be joined.
     */
    public function getEntityField(): ?string
    {
        return $this->entityField;
    }

    public function setEntityField(string $entityField): void
    {
        $this->entityField = $entityField;
    }

    /**
     * The additional condition which should apply to the join.
     */
    public function getCondition(): ?string
    {
        return $this->condition;
    }

    public function setCondition(string $condition): void
    {
        $this->condition = $condition;
    }

    /**
     * @return 'ON'|'WITH'
     */
    public function getConditionMethod(): string
    {
        return $this->conditionMethod;
    }

    /**
     * @param 'ON'|'WITH' $conditionMethod
     */
    public function setConditionMethod(string $conditionMethod): void
    {
        $this->conditionMethod = $conditionMethod;
    }

    /**
     * The method for the condition to apply (on or with).
     *
     * @return 'LEFT'|'INNER'|'RIGHT'
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param 'LEFT'|'INNER'|'RIGHT' $method
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
    }
}
