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

namespace Sulu\Article\Tests\Unit\Application\MessageHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Article\Application\Message\ApplyWorkflowTransitionArticleMessage;
use Sulu\Article\Application\MessageHandler\ApplyWorkflowTransitionArticleMessageHandler;
use Sulu\Article\Domain\Event\ArticleWorkflowTransitionAppliedEvent;
use Sulu\Article\Domain\Model\Article;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;

#[CoversClass(ApplyWorkflowTransitionArticleMessageHandler::class)]
#[CoversClass(ArticleWorkflowTransitionAppliedEvent::class)]
class ApplyWorkflowTransitionArticleMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<ContentWorkflowInterface> */
    private ObjectProphecy $contentWorkflow;

    /** @var ObjectProphecy<DomainEventCollectorInterface> */
    private ObjectProphecy $domainEventCollector;

    /** @var ObjectProphecy<ActiveWorkflowTransitionRequestProviderInterface> */
    private ObjectProphecy $requestProvider;

    private ArticleInterface $article;

    /** @var DimensionContentInterface<ArticleInterface> */
    private DimensionContentInterface $dimensionContent;

    private ApplyWorkflowTransitionArticleMessageHandler $handler;

    protected function setUp(): void
    {
        $this->article = new Article('article-123');

        $repository = $this->prophesize(ArticleRepositoryInterface::class);
        $repository->getOneBy(['uuid' => 'article-123'], Argument::any())->willReturn($this->article);

        $this->dimensionContent = $this->prophesize(DimensionContentInterface::class)->reveal();
        $this->contentWorkflow = $this->prophesize(ContentWorkflowInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);
        $this->requestProvider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);

        $this->handler = $this->createHandler($this->requestProvider->reveal(), $repository->reveal());
    }

    public function testPublishWhileTheRequestIsNotApprovedCollectsTheEventThatMarksTheBypass(): void
    {
        $request = $this->createRequest();
        $this->requestProvider->find(ArticleInterface::RESOURCE_KEY, 'article-123', 'en')->willReturn($request);
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
        $this->requestProvider->find(ArticleInterface::RESOURCE_KEY, 'article-123', 'en')->willReturn($request);
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
        $this->requestProvider->find(ArticleInterface::RESOURCE_KEY, 'article-123', 'en')->willReturn(null);

        ($this->handler)($this->createMessage(WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH));

        $this->assertCollectedEventType('workflow_transition.publish', false);
    }

    /**
     * The website context has no request services, so the handler gets no provider there.
     */
    public function testPublishWithoutAProviderCollectsThePlainPublishEvent(): void
    {
        $this->contentWorkflow->apply(Argument::cetera())->willReturn($this->dimensionContent);
        $repository = $this->prophesize(ArticleRepositoryInterface::class);
        $repository->getOneBy(['uuid' => 'article-123'], Argument::any())->willReturn($this->article);

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
            ->collect(Argument::that(static function(ArticleWorkflowTransitionAppliedEvent $event) use ($expectedType, $expectedBypass): bool {
                return $expectedType === $event->getEventType() && $expectedBypass === $event->isReviewBypassed();
            }))
            ->shouldBeCalledOnce();
    }

    private function createHandler(?ActiveWorkflowTransitionRequestProviderInterface $requestProvider, ArticleRepositoryInterface $repository): ApplyWorkflowTransitionArticleMessageHandler
    {
        return new ApplyWorkflowTransitionArticleMessageHandler(
            $repository,
            $this->contentWorkflow->reveal(),
            $this->domainEventCollector->reveal(),
            $requestProvider,
        );
    }

    private function createMessage(string $transition): ApplyWorkflowTransitionArticleMessage
    {
        return new ApplyWorkflowTransitionArticleMessage(['uuid' => 'article-123'], 'en', $transition);
    }

    private function createRequest(): WorkflowTransitionRequest
    {
        return new WorkflowTransitionRequest(ArticleInterface::RESOURCE_KEY, 'article-123', 'en', 'default');
    }
}
