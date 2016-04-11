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
use Sulu\Component\Rest\ListBuilder\Expression\ExpressionInterface;

/**
 * Abstract definition for expressions used by the DoctrineListbuilder.
 */
abstract class AbstractDoctrineExpression implements ExpressionInterface
{
    /**
     * Returns a statement for an expression.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return string
     */
    abstract public function getStatement(QueryBuilder $queryBuilder);

    /**
     * Returns a unique id as string.
     *
     * @return string
     */
    public function getUniqueId()
    {
        return  str_replace('.', '_', uniqid('', true));
    }
}
