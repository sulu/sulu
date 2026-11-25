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

namespace Sulu\Page\Tests\Unit\Application\MessageHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionPageMessage;
use Sulu\Page\Application\MessageHandler\ApplyWorkflowTransitionPageMessageHandler;
use Sulu\Page\Domain\Event\PageWorkflowTransitionAppliedEvent;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

#[CoversClass(ApplyWorkflowTransitionPageMessageHandler::class)]
#[CoversClass(PageWorkflowTransitionAppliedEvent::class)]
class ApplyWorkflowTransitionPageMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<ContentWorkflowInterface> */
    private ObjectProphecy $contentWorkflow;

    /** @var ObjectProphecy<DomainEventCollectorInterface> */
    private ObjectProphecy $domainEventCollector;

    /** @var ObjectProphecy<ActiveWorkflowTransitionRequestProviderInterface> */
    private ObjectProphecy $requestProvider;

    private PageInterface $page;

    /** @var DimensionContentInterface<PageInterface> */
    private DimensionContentInterface $dimensionContent;

    private ApplyWorkflowTransitionPageMessageHandler $handler;

    protected function setUp(): void
    {
        $this->page = new Page('page-123');

        $repository = $this->prophesize(PageRepositoryInterface::class);
        $repository->getOneBy(['uuid' => 'page-123'], Argument::any())->willReturn($this->page);

        $this->dimensionContent = $this->prophesize(DimensionContentInterface::class)->reveal();
        $this->contentWorkflow = $this->prophesize(ContentWorkflowInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);
        $this->requestProvider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);

        $this->handler = $this->createHandler($this->requestProvider->reveal(), $repository->reveal());
    }

    public function testPublishWhileTheRequestIsNotApprovedCollectsTheEventThatMarksTheBypass(): void
    {
        $request = $this->createRequest();
        $this->requestProvider->find(PageInterface::RESOURCE_KEY, 'page-123', 'en')->willReturn($request);
        $dimensionContent = $this->dimensionContent;
        $this->contentWorkflow->apply(Argument::cetera())->will(static function() use ($request, $dimensionContent): DimensionContentInterface {
            $request->bypass();

            return $dimensionContent;
        });

        ($this->handler)($this->createMessage(WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH));

        $this->assertCollectedEventType('workflow_transition.publish_without_review', true);
    }

    public function testPublishOfAnApprovedRequestCollectsThePlainPublishEvent(): void
    {
        $request = $this->createRequest();
        $this->requestProvider->find(PageInterface::RESOURCE_KEY, 'page-123', 'en')->willReturn($request);
        $dimensionContent = $this->dimensionContent;
        $this->contentWorkflow->apply(Argument::cetera())->will(static function() use ($request, $dimensionContent): DimensionContentInterface {
            $request->publish();

            return $dimensionContent;
        });

        ($this->handler)($this->createMessage(WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH));

        $this->assertCollectedEventType('workflow_transition.publish', false);
    }

    public function testPublishWithoutARequestCollectsThePlainPublishEvent(): void
    {
        $this->contentWorkflow->apply(Argument::cetera())->willReturn($this->dimensionContent);
        $this->requestProvider->find(PageInterface::RESOURCE_KEY, 'page-123', 'en')->willReturn(null);

        ($this->handler)($this->createMessage(WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH));

        $this->assertCollectedEventType('workflow_transition.publish', false);
    }

    /**
     * The website context has no request services, so the handler gets no provider there.
     */
    public function testPublishWithoutAProviderCollectsThePlainPublishEvent(): void
    {
        $this->contentWorkflow->apply(Argument::cetera())->willReturn($this->dimensionContent);
        $repository = $this->prophesize(PageRepositoryInterface::class);
        $repository->getOneBy(['uuid' => 'page-123'], Argument::any())->willReturn($this->page);

        $handler = $this->createHandler(null, $repository->reveal());
        $handler($this->createMessage(WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH));

        $this->assertCollectedEventType('workflow_transition.publish', false);
    }

    public function testAnotherTransitionNeverLooksForARequest(): void
    {
        $this->contentWorkflow->apply(Argument::cetera())->willReturn($this->dimensionContent);
        $this->requestProvider->find(Argument::cetera())->shouldNotBeCalled();

        ($this->handler)($this->createMessage(WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH));

        $this->assertCollectedEventType('workflow_transition.unpublish', false);
    }

    private function assertCollectedEventType(string $expectedType, bool $expectedBypass): void
    {
        $this->domainEventCollector
            ->collect(Argument::that(static function(PageWorkflowTransitionAppliedEvent $event) use ($expectedType, $expectedBypass): bool {
                return $expectedType === $event->getEventType() && $expectedBypass === $event->isReviewBypassed();
            }))
            ->shouldBeCalledOnce();
    }

    private function createHandler(?ActiveWorkflowTransitionRequestProviderInterface $requestProvider, PageRepositoryInterface $repository): ApplyWorkflowTransitionPageMessageHandler
    {
        return new ApplyWorkflowTransitionPageMessageHandler(
            $repository,
            $this->contentWorkflow->reveal(),
            $this->domainEventCollector->reveal(),
            $requestProvider,
        );
    }

    private function createMessage(string $transition): ApplyWorkflowTransitionPageMessage
    {
        return new ApplyWorkflowTransitionPageMessage(['uuid' => 'page-123'], 'en', $transition);
    }

    private function createRequest(): WorkflowTransitionRequest
    {
        return new WorkflowTransitionRequest(PageInterface::RESOURCE_KEY, 'page-123', 'en', 'default');
    }
}
