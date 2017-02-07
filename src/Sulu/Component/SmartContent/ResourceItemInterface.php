<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent;

/**
 * Interface for ResourceItem.
 */
interface ResourceItemInterface
{
    /**
     * Returns the resource which belongs to the item.
     *
     * @return mixed
     */
    public function getResource();

    /**
     * Returns id of item.
     *
     * @return string
     */
    public function getId();
}
