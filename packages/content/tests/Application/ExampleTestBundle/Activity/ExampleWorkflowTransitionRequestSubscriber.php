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
use Sulu\Content\Application\WorkflowTransitionRequest\Event\WorkflowTransitionRequestActionEvent;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ExampleWorkflowTransitionRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly DomainEventCollectorInterface $domainEventCollector)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [WorkflowTransitionRequestActionEvent::class => 'onAction'];
    }

    public function onAction(WorkflowTransitionRequestActionEvent $event): void
    {
        $request = $event->getWorkflowTransitionRequest();

        if (Example::RESOURCE_KEY !== $request->getResourceKey()) {
            return;
        }

        $this->domainEventCollector->collect(
            new ExampleWorkflowTransitionRequestEvent($request, $event->getAction(), $event->getContext()),
        );
    }
}
