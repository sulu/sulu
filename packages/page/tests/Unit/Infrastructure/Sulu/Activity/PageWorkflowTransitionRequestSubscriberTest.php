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

namespace Sulu\Page\Tests\Unit\Infrastructure\Sulu\Activity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Event\WorkflowTransitionRequestActionEvent;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Page\Domain\Event\PageWorkflowTransitionRequestEvent;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Infrastructure\Sulu\Activity\PageWorkflowTransitionRequestSubscriber;

#[CoversClass(PageWorkflowTransitionRequestSubscriber::class)]
class PageWorkflowTransitionRequestSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<PageRepositoryInterface>
     */
    private ObjectProphecy $pageRepository;

    /**
     * @var ObjectProphecy<DomainEventCollectorInterface>
     */
    private ObjectProphecy $domainEventCollector;

    /**
     * @var ObjectProphecy<DomainEventDispatcherInterface>
     */
    private ObjectProphecy $domainEventDispatcher;

    private PageWorkflowTransitionRequestSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->pageRepository = $this->prophesize(PageRepositoryInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);
        $this->domainEventDispatcher = $this->prophesize(DomainEventDispatcherInterface::class);

        $this->subscriber = new PageWorkflowTransitionRequestSubscriber(
            $this->pageRepository->reveal(),
            $this->domainEventCollector->reveal(),
            $this->domainEventDispatcher->reveal(),
        );
    }

    public function testSubscribesToTheActionEvent(): void
    {
        $this->assertArrayHasKey(WorkflowTransitionRequestActionEvent::class, PageWorkflowTransitionRequestSubscriber::getSubscribedEvents());
    }

    public function testCollectsTheDomainEventForItsResourceKey(): void
    {
        $page = $this->prophesize(PageInterface::class)->reveal();
        $this->pageRepository->findOneBy(['uuid' => 'resource-1'], Argument::type('array'))->willReturn($page);

        $this->domainEventCollector->collect(Argument::that(
            static fn (object $event) => $event instanceof PageWorkflowTransitionRequestEvent
                && $page === $event->getPage()
                && 'workflow_transition_request.approved' === $event->getEventType()
                && 'de' === $event->getResourceLocale()
                && ['comment' => 'Fine'] === $event->getEventContext(),
        ))->shouldBeCalledOnce();

        $this->subscriber->onWorkflowTransitionRequestAction(new WorkflowTransitionRequestActionEvent(
            new WorkflowTransitionRequest(PageInterface::RESOURCE_KEY, 'resource-1', 'de', 'default'),
            WorkflowTransitionRequestActionEvent::APPROVED,
            ['comment' => 'Fine'],
        ));
    }

    public function testDispatchesValidatedRightAwayInsteadOfCollectingIt(): void
    {
        $page = $this->prophesize(PageInterface::class)->reveal();
        $this->pageRepository->findOneBy(['uuid' => 'resource-1'], Argument::type('array'))->willReturn($page);

        $this->domainEventDispatcher->dispatch(Argument::that(
            static fn (object $event) => $event instanceof PageWorkflowTransitionRequestEvent
                && 'workflow_transition_request.validated' === $event->getEventType()
                && ['approved' => 1, 'rejected' => 2] === $event->getEventContext(),
        ))->shouldBeCalledOnce();
        $this->domainEventCollector->collect(Argument::any())->shouldNotBeCalled();

        $this->subscriber->onWorkflowTransitionRequestAction(new WorkflowTransitionRequestActionEvent(
            new WorkflowTransitionRequest(PageInterface::RESOURCE_KEY, 'resource-1', 'de', 'default'),
            WorkflowTransitionRequestActionEvent::VALIDATED,
            ['approved' => 1, 'rejected' => 2],
        ));
    }

    public function testIgnoresOtherResourceKeys(): void
    {
        $this->pageRepository->findOneBy(Argument::cetera())->shouldNotBeCalled();
        $this->domainEventCollector->collect(Argument::any())->shouldNotBeCalled();

        $this->subscriber->onWorkflowTransitionRequestAction(new WorkflowTransitionRequestActionEvent(
            new WorkflowTransitionRequest('other', 'resource-1', 'de', 'default'),
            WorkflowTransitionRequestActionEvent::APPROVED,
            [],
        ));
    }

    public function testCollectsNothingWhenTheContentIsGone(): void
    {
        $this->pageRepository->findOneBy(Argument::cetera())->willReturn(null);
        $this->domainEventCollector->collect(Argument::any())->shouldNotBeCalled();

        $this->subscriber->onWorkflowTransitionRequestAction(new WorkflowTransitionRequestActionEvent(
            new WorkflowTransitionRequest(PageInterface::RESOURCE_KEY, 'resource-1', 'de', 'default'),
            WorkflowTransitionRequestActionEvent::VALIDATED,
            ['approved' => 1, 'rejected' => 0],
        ));
    }
}
