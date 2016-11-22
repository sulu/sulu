<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AutomationBundle\Events;

/**
 * Container for automation events.
 */
final class Events
{
    const TASK_CREATE_EVENT = 'sulu_automation.task.create';
    const TASK_UPDATE_EVENT = 'sulu_automation.task.update';
    const TASK_REMOVE_EVENT = 'sulu_automation.task.remove';

    private function __construct()
    {
    }
}
