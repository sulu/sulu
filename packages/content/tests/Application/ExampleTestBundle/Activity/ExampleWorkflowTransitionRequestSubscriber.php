<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Application\ExampleTestBundle\Activity;

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Event\WorkflowTransitionRequestActionEvent;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ExampleWorkflowTransitionRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly DomainEventCollectorInterface $domainEventCollector,
        private readonly DomainEventDispatcherInterface $domainEventDispatcher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [WorkflowTransitionRequestActionEvent::class => 'onAction'];
    }

    public function onAction(WorkflowTransitionRequestActionEvent $event): void
    {
        if (Example::RESOURCE_KEY !== $event->getResourceKey()) {
            return;
        }

        $domainEvent = new ExampleWorkflowTransitionRequestEvent(
            $event->getWorkflowTransitionRequest(),
            $event->getAction(),
            $event->getContext(),
        );

        if ($event->isWrittenWithoutFlush()) {
            $this->domainEventDispatcher->dispatch($domainEvent);

            return;
        }

        $this->domainEventCollector->collect($domainEvent);
    }
}
