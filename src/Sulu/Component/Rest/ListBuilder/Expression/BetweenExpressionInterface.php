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
 * Interfaces which provides the needed information to build a between-expression.
 */
interface BetweenExpressionInterface extends BasicExpressionInterface
{
    /**
     * Returns the start value used as first value in a between expression.
     *
     * @return array
     */
    public function getStart();

    /**
     * Returns the end value used as second value in a between expression.
     *
     * @return array
     */
    public function getEnd();
}
