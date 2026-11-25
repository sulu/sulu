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

namespace Sulu\Snippet\Tests\Unit\Application\MessageHandler;

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
use Sulu\Snippet\Application\Message\ApplyWorkflowTransitionSnippetMessage;
use Sulu\Snippet\Application\MessageHandler\ApplyWorkflowTransitionSnippetMessageHandler;
use Sulu\Snippet\Domain\Event\SnippetWorkflowTransitionAppliedEvent;
use Sulu\Snippet\Domain\Model\Snippet;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

#[CoversClass(ApplyWorkflowTransitionSnippetMessageHandler::class)]
#[CoversClass(SnippetWorkflowTransitionAppliedEvent::class)]
class ApplyWorkflowTransitionSnippetMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<ContentWorkflowInterface> */
    private ObjectProphecy $contentWorkflow;

    /** @var ObjectProphecy<DomainEventCollectorInterface> */
    private ObjectProphecy $domainEventCollector;

    /** @var ObjectProphecy<ActiveWorkflowTransitionRequestProviderInterface> */
    private ObjectProphecy $requestProvider;

    private SnippetInterface $snippet;

    /** @var DimensionContentInterface<SnippetInterface> */
    private DimensionContentInterface $dimensionContent;

    private ApplyWorkflowTransitionSnippetMessageHandler $handler;

    protected function setUp(): void
    {
        $this->snippet = new Snippet('snippet-123');

        $repository = $this->prophesize(SnippetRepositoryInterface::class);
        $repository->getOneBy(['uuid' => 'snippet-123'], Argument::any())->willReturn($this->snippet);

        $this->dimensionContent = $this->prophesize(DimensionContentInterface::class)->reveal();
        $this->contentWorkflow = $this->prophesize(ContentWorkflowInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);
        $this->requestProvider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);

        $this->handler = $this->createHandler($this->requestProvider->reveal(), $repository->reveal());
    }

    public function testPublishWhileTheRequestIsNotApprovedCollectsTheEventThatMarksTheBypass(): void
    {
        $request = $this->createRequest();
        $this->requestProvider->find(SnippetInterface::RESOURCE_KEY, 'snippet-123', 'en')->willReturn($request);
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
        $this->requestProvider->find(SnippetInterface::RESOURCE_KEY, 'snippet-123', 'en')->willReturn($request);
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
        $this->requestProvider->find(SnippetInterface::RESOURCE_KEY, 'snippet-123', 'en')->willReturn(null);

        ($this->handler)($this->createMessage(WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH));

        $this->assertCollectedEventType('workflow_transition.publish', false);
    }

    /**
     * The website context has no request services, so the handler gets no provider there.
     */
    public function testPublishWithoutAProviderCollectsThePlainPublishEvent(): void
    {
        $this->contentWorkflow->apply(Argument::cetera())->willReturn($this->dimensionContent);
        $repository = $this->prophesize(SnippetRepositoryInterface::class);
        $repository->getOneBy(['uuid' => 'snippet-123'], Argument::any())->willReturn($this->snippet);

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
            ->collect(Argument::that(static function(SnippetWorkflowTransitionAppliedEvent $event) use ($expectedType, $expectedBypass): bool {
                return $expectedType === $event->getEventType() && $expectedBypass === $event->isReviewBypassed();
            }))
            ->shouldBeCalledOnce();
    }

    private function createHandler(?ActiveWorkflowTransitionRequestProviderInterface $requestProvider, SnippetRepositoryInterface $repository): ApplyWorkflowTransitionSnippetMessageHandler
    {
        return new ApplyWorkflowTransitionSnippetMessageHandler(
            $repository,
            $this->contentWorkflow->reveal(),
            $this->domainEventCollector->reveal(),
            $requestProvider,
        );
    }

    private function createMessage(string $transition): ApplyWorkflowTransitionSnippetMessage
    {
        return new ApplyWorkflowTransitionSnippetMessage(['uuid' => 'snippet-123'], 'en', $transition);
    }

    private function createRequest(): WorkflowTransitionRequest
    {
        return new WorkflowTransitionRequest(SnippetInterface::RESOURCE_KEY, 'snippet-123', 'en', 'default');
    }
}
