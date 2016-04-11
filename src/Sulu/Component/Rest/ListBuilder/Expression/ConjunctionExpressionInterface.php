<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Expression;

/**
 * Interfaces which provides the needed information to build a conjunction-expression.
 */
interface ConjunctionExpressionInterface extends ExpressionInterface
{
    /**
     * Array of expressions which will be combined.
     *
     * @return ExpressionInterface[]
     */
    public function getExpressions();

    /**
     * Returns an array of the used field names of the current expression and all subexpressions.
     *
     * @return array
     */
    public function getFieldNames();
}
