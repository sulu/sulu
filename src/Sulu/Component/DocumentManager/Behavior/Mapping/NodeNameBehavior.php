<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Behavior\Mapping;

/**
 * Populate the node name.
 */
interface NodeNameBehavior
{
    /**
     * Return the node name.
     *
     * NOTE: You must add a $nodeName property to your class
     *
     * @return string
     */
    public function getNodeName();
}
