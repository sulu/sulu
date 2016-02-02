<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Expression\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sulu\Component\Rest\ListBuilder\Expression\BasicExpressionInterface;
use Sulu\Component\Rest\ListBuilder\Expression\ConjunctionExpressionInterface;
use Sulu\Component\Rest\ListBuilder\Expression\Exception\InsufficientExpressionsException;

/**
 * This class is used as base class for the conjunctions expressions AND and OR.
 */
class DoctrineConjunctionExpression extends AbstractDoctrineExpression implements ConjunctionExpressionInterface
{
    /**
     * @var string
     */
    protected $conjunction;

    /**
     * @var AbstractDoctrineExpression[]
     */
    protected $expressions;

    /**
     * DoctrineAndExpression constructor.
     *
     * @param string $conjunction
     * @param AbstractDoctrineExpression[] $expressions
     *
     * @throws InsufficientExpressionsException
     */
    public function __construct($conjunction, array $expressions)
    {
        if (count($expressions) < 2) {
            throw new InsufficientExpressionsException($expressions);
        }

        $this->expressions = $expressions;
        $this->conjunction = $conjunction;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatement(QueryBuilder $queryBuilder)
    {
        $statements = [];
        foreach ($this->expressions as $expression) {
            $statements[] = $expression->getStatement($queryBuilder);
        }

        return implode(' ' . $this->conjunction . ' ', $statements);
    }

    /**
     * {@inheritdoc}
     */
    public function getExpressions()
    {
        return $this->expressions;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldNames()
    {
        $result = [];
        foreach ($this->expressions as $expression) {
            if ($expression instanceof ConjunctionExpressionInterface) {
                $result = array_merge($result, $expression->getFieldNames());
            } elseif ($expression instanceof BasicExpressionInterface) {
                $result[] = $expression->getFieldName();
            }
        }

        return $result;
    }
}
