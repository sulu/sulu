<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AutomationBundle\Serializer;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Sulu\Bundle\AutomationBundle\TaskHandler\AutomationTaskHandlerInterface;
use Sulu\Bundle\AutomationBundle\Tasks\Model\TaskInterface;
use Task\Handler\TaskHandlerFactoryInterface;
use Task\Storage\TaskExecutionRepositoryInterface;

/**
 * Extend serialization of tasks.
 */
class TaskSerializerSubscriber implements EventSubscriberInterface
{
    /**
     * @var TaskHandlerFactoryInterface
     */
    private $handlerFactory;

    /**
     * @var TaskExecutionRepositoryInterface
     */
    private $taskExecutionRepository;

    /**
     * @param TaskHandlerFactoryInterface $handlerFactory
     * @param TaskExecutionRepositoryInterface $taskExecutionRepository
     */
    public function __construct(
        TaskHandlerFactoryInterface $handlerFactory,
        TaskExecutionRepositoryInterface $taskExecutionRepository
    ) {
        $this->handlerFactory = $handlerFactory;
        $this->taskExecutionRepository = $taskExecutionRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onTaskSerialize',
            ],
        ];
    }

    /**
     * Append task-name to task-serialization.
     *
     * @param ObjectEvent $event
     */
    public function onTaskSerialize(ObjectEvent $event)
    {
        $object = $event->getObject();
        if (!$object instanceof TaskInterface) {
            return;
        }

        $handler = $this->handlerFactory->create($object->getHandlerClass());
        if ($handler instanceof AutomationTaskHandlerInterface) {
            $event->getVisitor()->addData('taskName', $handler->getConfiguration()->getTitle());
        }

        $executions = $this->taskExecutionRepository->findByTaskUuid($object->getTaskId());
        if (0 < count($executions)) {
            $event->getVisitor()->addData('status', $executions[0]->getStatus());
        }
    }
}
