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
 * Interfaces which provides the needed information to build a where-expression.
 */
interface WhereExpressionInterface extends BasicExpressionInterface
{
    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @return mixed
     */
    public function getComparator();
}
