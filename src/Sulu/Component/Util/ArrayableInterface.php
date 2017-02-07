<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util;

/**
 * An interface to apply to classes which must serialize their contents
 * as an array.
 */
interface ArrayableInterface
{
    /**
     * Return a representation of this object as an array.
     *
     * @param int $depth - Optional depth parameter. May or may not be used
     *                   by the implementing object
     */
    public function toArray($depth = null);
}
