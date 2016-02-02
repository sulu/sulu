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
 * The resource segment is a URI segment which represents the
 * implementing document in the full URL.
 */
interface ResourceSegmentBehavior
{
    /**
     * Return the resource segment.
     *
     * @return string
     */
    public function getResourceSegment();

    /**
     * Set the resource segment.
     *
     * @param string $resourceSegment
     */
    public function setResourceSegment($resourceSegment);
}
