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
     * @param TaskHandlerFactoryInterface $handlerFactory
     */
    public function __construct(TaskHandlerFactoryInterface $handlerFactory)
    {
        $this->handlerFactory = $handlerFactory;
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
    }
}
