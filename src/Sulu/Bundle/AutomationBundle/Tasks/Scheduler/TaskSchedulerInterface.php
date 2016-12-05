<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AutomationBundle\Tasks\Scheduler;

use Sulu\Bundle\AutomationBundle\Exception\TaskExpiredException;
use Sulu\Bundle\AutomationBundle\Tasks\Model\TaskInterface;

/**
 * Interface for task-scheduler.
 */
interface TaskSchedulerInterface
{
    /**
     * Schedule given task.
     *
     * @param TaskInterface $task
     */
    public function schedule(TaskInterface $task);

    /**
     * Reschedule given task.
     *
     * @param TaskInterface $task
     *
     * @throws TaskExpiredException
     */
    public function reschedule(TaskInterface $task);

    /**
     * Unschedules given task.
     *
     * @param TaskInterface $task
     */
    public function remove(TaskInterface $task);
}
