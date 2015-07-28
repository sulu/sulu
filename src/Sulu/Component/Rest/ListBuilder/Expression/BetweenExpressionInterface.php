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
 * Interfaces which provides the needed information to build a between-expression.
 */
interface BetweenExpressionInterface extends BasicExpressionInterface
{
    /**
     * @return array
     */
    public function getStart();

    /**
     * @return array
     */
    public function getEnd();
}
