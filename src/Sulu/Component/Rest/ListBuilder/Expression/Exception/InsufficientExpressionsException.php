<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * @var AbstractDoctrineExpression[]
     */
    protected $expressions;

    public function __construct($expressions)
    {
        $this->message = 'An insufficient number of expressions has been provided';
        $this->expressions = $expressions;
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
