<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Expression\Exception;

use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\AbstractDoctrineExpression;

/**
 * Exceptions is throw when insufficient expressions have been provided.
 */
class InsufficientExpressionsException extends ExpressionException
{
    /**
     * @param AbstractDoctrineExpression[] $expressions
     */
    public function __construct(protected $expressions)
    {
        $this->message = 'An insufficient number of expressions has been provided';
        parent::__construct($this->message);
    }

    /**
     * @return AbstractDoctrineExpression[]
     */
    public function getExpressions()
    {
        return $this->expressions;
    }
}
