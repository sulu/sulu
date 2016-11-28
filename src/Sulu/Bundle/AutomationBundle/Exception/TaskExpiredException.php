<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AutomationBundle\Exception;

use Sulu\Bundle\AutomationBundle\Tasks\Model\TaskInterface;

/**
 * Will be thrown when an expired task will be edited.
 */
class TaskExpiredException extends \Exception
{
    /**
     * @var TaskInterface
     */
    private $task;

    /**
     * @param TaskInterface $task
     */
    public function __construct(TaskInterface $task)
    {
        parent::__construct(sprintf('Task "%s" is already expired.', $task->getUuid()));

        $this->task = $task;
    }

    /**
     * Returns task.
     *
     * @return TaskInterface
     */
    public function getTask()
    {
        return $this->task;
    }
}
