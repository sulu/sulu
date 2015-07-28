<?php
/*
 * This file is part of the Sulu CMS.
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
     * @return string
     */
    public function getConjunction();

    /**
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
