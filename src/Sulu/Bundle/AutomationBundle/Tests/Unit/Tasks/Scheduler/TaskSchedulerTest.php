<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AutomationBundle\Tests\Unit\Scheduler;

use Prophecy\Argument;
use Sulu\Bundle\AutomationBundle\TaskHandler\AutomationTaskHandlerInterface;
use Sulu\Bundle\AutomationBundle\Tasks\Model\TaskInterface;
use Sulu\Bundle\AutomationBundle\Tasks\Scheduler\TaskScheduler;
use Sulu\Bundle\AutomationBundle\Tests\Handler\TestHandler;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Task\Builder\TaskBuilderInterface;
use Task\Execution\TaskExecutionInterface;
use Task\Handler\TaskHandlerFactoryInterface;
use Task\Scheduler\TaskSchedulerInterface;
use Task\Storage\TaskExecutionRepositoryInterface;
use Task\Storage\TaskRepositoryInterface;
use Task\TaskStatus;

/**
 * Tests for class task-event-listener.
 */
class TaskSchedulerTest extends \PHPUnit_Framework_TestCase
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
     * @var TaskSchedulerInterface
     */
    private $taskScheduler;

    /**
     * @var TaskScheduler
     */
    private $taskEventListener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->taskRepository = $this->prophesize(TaskRepositoryInterface::class);
        $this->taskExecutionRepository = $this->prophesize(TaskExecutionRepositoryInterface::class);
        $this->taskHandlerFactory = $this->prophesize(TaskHandlerFactoryInterface::class);
        $this->taskScheduler = $this->prophesize(TaskSchedulerInterface::class);

        $this->taskEventListener = new TaskScheduler(
            $this->taskRepository->reveal(),
            $this->taskExecutionRepository->reveal(),
            $this->taskHandlerFactory->reveal(),
            $this->taskScheduler->reveal()
        );
    }

    public function testSchedule()
    {
        $task = $this->prophesize(TaskInterface::class);

        $this->prepareCreateWorkload($task);
        $this->prepareScheduleTask($task);

        $this->taskEventListener->schedule($task->reveal());
    }

    public function testReschedule()
    {
        $task = $this->prophesize(TaskInterface::class);

        $task->getTaskId()->willReturn('123-312-123');
        $phpTask = $this->prophesize(\Task\TaskInterface::class);
        $this->taskRepository->findByUuid('123-312-123')->willReturn($phpTask->reveal());
        $phpTaskExecution = $this->prophesize(TaskExecutionInterface::class);
        $this->taskExecutionRepository->findByTask($phpTask->reveal())->willReturn([$phpTaskExecution->reveal()]);

        $phpTask->getFirstExecution()->willReturn(new \DateTime('-1 day'));
        $task->getSchedule()->willReturn(new \DateTime('1 day'));
        $phpTaskExecution->getStatus()->willReturn(TaskStatus::PLANNED);

        $this->taskRepository->remove($phpTask)->shouldBeCalled();

        $this->prepareCreateWorkload($task);
        $this->prepareScheduleTask($task);

        $this->taskEventListener->reschedule($task->reveal());
    }

    public function testRemove()
    {
        $task = $this->prophesize(TaskInterface::class);

        $task->getTaskId()->willReturn('123-312-123');
        $phpTask = $this->prophesize(\Task\TaskInterface::class);
        $this->taskRepository->findByUuid('123-312-123')->willReturn($phpTask->reveal());
        $this->taskRepository->remove($phpTask)->shouldBeCalled();

        $this->taskEventListener->remove($task->reveal());
    }

    private function prepareCreateWorkload($task, $entityClass = '\TestClass', $entityId = 1, $locale = 'de')
    {
        $handler = $this->prophesize(AutomationTaskHandlerInterface::class);

        $task->getHandlerClass()->willReturn(TestHandler::class);
        $this->taskHandlerFactory->create(TestHandler::class)->willReturn($handler->reveal());

        $task->getEntityClass()->willReturn($entityClass);
        $task->getEntityId()->willReturn($entityId);
        $task->getLocale()->willReturn($locale);
        $handler->configureOptionsResolver(Argument::type(OptionsResolver::class))
            ->shouldBeCalled()
            ->willReturnArgument(0);
    }

    private function prepareScheduleTask($task, $uuid = '123-123-123', $schedule = '+1 day')
    {
        $date = new \DateTime($schedule);

        $taskBuilder = $this->prophesize(TaskBuilderInterface::class);
        $this->taskScheduler->createTask(TestHandler::class, Argument::any())->willReturn($taskBuilder->reveal());

        $task->getSchedule()->willReturn($date);
        $taskBuilder->executeAt($date)->shouldBeCalled()->willReturn($taskBuilder->reveal());

        $phpTask = $this->prophesize(\Task\TaskInterface::class);
        $phpTask->getUuid()->willReturn($uuid);
        $taskBuilder->schedule()->shouldBeCalled()->willReturn($phpTask->reveal());

        $task->setTaskId($uuid)->shouldBeCalled();
    }
}
