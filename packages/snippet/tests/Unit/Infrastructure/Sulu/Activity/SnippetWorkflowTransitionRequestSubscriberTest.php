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

namespace Sulu\Snippet\Tests\Unit\Infrastructure\Sulu\Activity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Event\WorkflowTransitionRequestActionEvent;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Snippet\Domain\Event\SnippetWorkflowTransitionRequestEvent;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;
use Sulu\Snippet\Infrastructure\Sulu\Activity\SnippetWorkflowTransitionRequestSubscriber;

#[CoversClass(SnippetWorkflowTransitionRequestSubscriber::class)]
class SnippetWorkflowTransitionRequestSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SnippetRepositoryInterface>
     */
    private ObjectProphecy $snippetRepository;

    /**
     * @var ObjectProphecy<DomainEventCollectorInterface>
     */
    private ObjectProphecy $domainEventCollector;

    /**
     * @var ObjectProphecy<DomainEventDispatcherInterface>
     */
    private ObjectProphecy $domainEventDispatcher;

    private SnippetWorkflowTransitionRequestSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->snippetRepository = $this->prophesize(SnippetRepositoryInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);
        $this->domainEventDispatcher = $this->prophesize(DomainEventDispatcherInterface::class);

        $this->subscriber = new SnippetWorkflowTransitionRequestSubscriber(
            $this->snippetRepository->reveal(),
            $this->domainEventCollector->reveal(),
            $this->domainEventDispatcher->reveal(),
        );
    }

    public function testSubscribesToTheActionEvent(): void
    {
        $this->assertArrayHasKey(WorkflowTransitionRequestActionEvent::class, SnippetWorkflowTransitionRequestSubscriber::getSubscribedEvents());
    }

    public function testCollectsTheDomainEventForItsResourceKey(): void
    {
        $snippet = $this->prophesize(SnippetInterface::class)->reveal();
        $this->snippetRepository->findOneBy(['uuid' => 'resource-1'], Argument::type('array'))->willReturn($snippet);

        $this->domainEventCollector->collect(Argument::that(
            static fn (object $event) => $event instanceof SnippetWorkflowTransitionRequestEvent
                && $snippet === $event->getSnippet()
                && 'workflow_transition_request.approved' === $event->getEventType()
                && 'de' === $event->getResourceLocale()
                && ['comment' => 'Fine'] === $event->getEventContext(),
        ))->shouldBeCalledOnce();

        $this->subscriber->onWorkflowTransitionRequestAction(new WorkflowTransitionRequestActionEvent(
            new WorkflowTransitionRequest(SnippetInterface::RESOURCE_KEY, 'resource-1', 'de', 'default'),
            WorkflowTransitionRequestActionEvent::APPROVED,
            ['comment' => 'Fine'],
        ));
    }

    public function testDispatchesValidatedRightAwayInsteadOfCollectingIt(): void
    {
        $snippet = $this->prophesize(SnippetInterface::class)->reveal();
        $this->snippetRepository->findOneBy(['uuid' => 'resource-1'], Argument::type('array'))->willReturn($snippet);

        $this->domainEventDispatcher->dispatch(Argument::that(
            static fn (object $event) => $event instanceof SnippetWorkflowTransitionRequestEvent
                && 'workflow_transition_request.validated' === $event->getEventType()
                && ['approved' => 1, 'rejected' => 2] === $event->getEventContext(),
        ))->shouldBeCalledOnce();
        $this->domainEventCollector->collect(Argument::any())->shouldNotBeCalled();

        $this->subscriber->onWorkflowTransitionRequestAction(new WorkflowTransitionRequestActionEvent(
            new WorkflowTransitionRequest(SnippetInterface::RESOURCE_KEY, 'resource-1', 'de', 'default'),
            WorkflowTransitionRequestActionEvent::VALIDATED,
            ['approved' => 1, 'rejected' => 2],
        ));
    }

    public function testIgnoresOtherResourceKeys(): void
    {
        $this->snippetRepository->findOneBy(Argument::cetera())->shouldNotBeCalled();
        $this->domainEventCollector->collect(Argument::any())->shouldNotBeCalled();

        $this->subscriber->onWorkflowTransitionRequestAction(new WorkflowTransitionRequestActionEvent(
            new WorkflowTransitionRequest('other', 'resource-1', 'de', 'default'),
            WorkflowTransitionRequestActionEvent::APPROVED,
            [],
        ));
    }

    public function testCollectsNothingWhenTheContentIsGone(): void
    {
        $this->snippetRepository->findOneBy(Argument::cetera())->willReturn(null);
        $this->domainEventCollector->collect(Argument::any())->shouldNotBeCalled();

        $this->subscriber->onWorkflowTransitionRequestAction(new WorkflowTransitionRequestActionEvent(
            new WorkflowTransitionRequest(SnippetInterface::RESOURCE_KEY, 'resource-1', 'de', 'default'),
            WorkflowTransitionRequestActionEvent::VALIDATED,
            ['approved' => 1, 'rejected' => 0],
        ));
    }
}
