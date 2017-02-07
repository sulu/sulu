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
 * Document which contains robot configurations (no-index, no-follow).
 */
interface RobotBehavior
{
    /**
     * Returns true if no-follow is enabled.
     *
     * @return bool
     */
    public function isNoFollow();

    /**
     * Returns true if no-index is enabled.
     *
     * @return bool
     */
    public function isNoIndex();
}
