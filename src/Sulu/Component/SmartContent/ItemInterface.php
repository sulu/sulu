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
 * Item to display in smart-content UI.
 */
interface ItemInterface extends ResourceItemInterface
{
    /**
     * Returns title of the item.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Returns URL to image.
     *
     * @return string
     */
    public function getImage();
}
