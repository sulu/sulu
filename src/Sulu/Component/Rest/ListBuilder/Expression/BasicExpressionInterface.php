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
 * Interface for basic expressions which have no sub expressions.
 */
interface BasicExpressionInterface extends ExpressionInterface
{
    /**
     * Returns the fieldname.
     *
     * @return string
     */
    public function getFieldName();
}
