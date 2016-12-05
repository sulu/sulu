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
use Sulu\Bundle\AutomationBundle\Exception\TaskHandlerNotSupportedException;
use Sulu\Bundle\AutomationBundle\TaskHandler\AutomationTaskHandlerInterface;
use Sulu\Bundle\AutomationBundle\Tasks\Model\TaskInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Task\Handler\TaskHandlerFactoryInterface;
use Task\Scheduler\TaskSchedulerInterface as PHPTaskSchedulerInterface;
use Task\Storage\TaskExecutionRepositoryInterface;
use Task\Storage\TaskRepositoryInterface;
use Task\TaskInterface as PHPTaskInterface;
use Task\TaskStatus;

/**
 * Integrates php-task library into sulu.
 */
class TaskScheduler implements TaskSchedulerInterface
{
    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepository;

    /**
     * @var TaskExecutionRepositoryInterface
     */
    private $taskExecutionRepository;

    /**
     * @var TaskHandlerFactoryInterface
     */
    private $taskHandlerFactory;

    /**
     * @var PHPTaskSchedulerInterface
     */
    private $taskScheduler;

    /**
     * @param TaskRepositoryInterface $phpTaskRepository
     * @param TaskExecutionRepositoryInterface $phpTaskExecutionRepository
     * @param TaskHandlerFactoryInterface $taskHandlerFactory
     * @param PHPTaskSchedulerInterface $taskScheduler
     */
    public function __construct(
        TaskRepositoryInterface $phpTaskRepository,
        TaskExecutionRepositoryInterface $phpTaskExecutionRepository,
        TaskHandlerFactoryInterface $taskHandlerFactory,
        PHPTaskSchedulerInterface $taskScheduler
    ) {
        $this->taskRepository = $phpTaskRepository;
        $this->taskExecutionRepository = $phpTaskExecutionRepository;
        $this->taskHandlerFactory = $taskHandlerFactory;
        $this->taskScheduler = $taskScheduler;
    }

    /**
     * {@inheritdoc}
     */
    public function schedule(TaskInterface $task)
    {
        $workload = $this->createWorkload($task);
        $task->setTaskId($this->scheduleTask($task, $workload)->getUuid());
    }

    /**
     * {@inheritdoc}
     */
    public function reschedule(TaskInterface $task)
    {
        $workload = $this->createWorkload($task);

        $phpTask = $this->taskRepository->findByUuid($task->getTaskId());
        $executions = $this->taskExecutionRepository->findByTask($phpTask);

        if ($task->getSchedule() === $phpTask->getFirstExecution()
            && $task->getHandlerClass() === $phpTask->getHandlerClass()
            && $workload === $phpTask->getWorkload()
        ) {
            return;
        }

        // cannot update finished tasks
        if (TaskStatus::PLANNED !== $executions[0]->getStatus()) {
            throw new TaskExpiredException($task);
        }

        $this->taskRepository->remove($phpTask);
        $task->setTaskId($this->scheduleTask($task, $workload)->getUuid());
    }

    /**
     * {@inheritdoc}
     */
    public function remove(TaskInterface $task)
    {
        $phpTask = $this->taskRepository->findByUuid($task->getTaskId());
        $this->taskRepository->remove($phpTask);
    }

    /**
     * Schedule php-task.
     *
     * @param TaskInterface $task
     * @param array $workload
     *
     * @return PHPTaskInterface
     */
    private function scheduleTask(TaskInterface $task, array $workload)
    {
        return $this->taskScheduler->createTask($task->getHandlerClass(), $workload)
            ->executeAt($task->getSchedule())
            ->schedule();
    }

    /**
     * Create and validate workload for given task.
     *
     * @param TaskInterface $task
     *
     * @return array
     *
     * @throws TaskHandlerNotSupportedException
     */
    private function createWorkload(TaskInterface $task)
    {
        // TODO get from additional form of handler
        $workload = [];

        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefaults(
            [
                'class' => $task->getEntityClass(),
                'id' => $task->getEntityId(),
                'locale' => $task->getLocale(),
            ]
        );

        $handler = $this->taskHandlerFactory->create($task->getHandlerClass());
        if (!$handler instanceof AutomationTaskHandlerInterface) {
            throw new TaskHandlerNotSupportedException($handler, $task);
        }

        $optionsResolver = $handler->configureOptionsResolver($optionsResolver);

        return $optionsResolver->resolve($workload);
    }
}
