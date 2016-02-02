<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Scheduler;

/**
 * Defines the interface for a scheduled task.
 */
interface TaskInterface
{
    /**
     * Periodically called by the Scheduler.
     */
    public function execute();
}
