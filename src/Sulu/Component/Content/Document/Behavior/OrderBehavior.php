<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Behavior;

/**
 * The node of the implementing document will have an "order" property
 * populated which represents its position in the set of the parents children.
 */
interface OrderBehavior
{
    /**
     * Return the absolute order value of this document.
     *
     * @return int
     */
    public function getSuluOrder();

    /**
     * Set the absolute order value of this document.
     *
     * @param int $order
     */
    public function setSuluOrder($order);
}
