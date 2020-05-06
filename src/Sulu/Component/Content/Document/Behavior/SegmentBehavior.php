<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Behavior;

/**
 * The implementing document can have a single segment applied to it.
 *
 * The segment indicates in which segment the implementing document will appear in.
 */
interface SegmentBehavior
{
    public function getSegment(): ?string;

    public function setSegment(?string $segment = null);
}
