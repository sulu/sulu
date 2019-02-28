<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Expression\Doctrine;

use Doctrine\ORM\QueryBuilder;

/**
 * Represents a NOT expression for doctrine - needs another expression to negate.
 */
class DoctrineNotExpression extends AbstractDoctrineExpression
{
    /**
     * @var AbstractDoctrineExpression
     */
    private $expression;

    /**
     * DoctrineNotExpression constructor.
     *
     * @param AbstractDoctrineExpression $expression
     */
    public function __construct(AbstractDoctrineExpression $expression)
    {
        $this->expression = $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatement(QueryBuilder $queryBuilder)
    {
        return 'NOT(' . $this->expression->getStatement($queryBuilder) . ')';
    }
}
