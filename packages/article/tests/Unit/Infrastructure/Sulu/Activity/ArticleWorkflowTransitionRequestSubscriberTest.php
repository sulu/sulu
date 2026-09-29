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

namespace Sulu\Article\Tests\Unit\Infrastructure\Sulu\Activity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Article\Domain\Event\ArticleWorkflowTransitionRequestEvent;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Article\Infrastructure\Sulu\Activity\ArticleWorkflowTransitionRequestSubscriber;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Event\WorkflowTransitionRequestActionEvent;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;

#[CoversClass(ArticleWorkflowTransitionRequestSubscriber::class)]
class ArticleWorkflowTransitionRequestSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ArticleRepositoryInterface>
     */
    private ObjectProphecy $articleRepository;

    /**
     * @var ObjectProphecy<DomainEventCollectorInterface>
     */
    private ObjectProphecy $domainEventCollector;

    private ArticleWorkflowTransitionRequestSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->articleRepository = $this->prophesize(ArticleRepositoryInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $this->subscriber = new ArticleWorkflowTransitionRequestSubscriber(
            $this->articleRepository->reveal(),
            $this->domainEventCollector->reveal(),
        );
    }

    public function testSubscribesToTheActionEvent(): void
    {
        $this->assertArrayHasKey(WorkflowTransitionRequestActionEvent::class, ArticleWorkflowTransitionRequestSubscriber::getSubscribedEvents());
    }

    public function testCollectsTheDomainEventForItsResourceKey(): void
    {
        $article = $this->prophesize(ArticleInterface::class)->reveal();
        $this->articleRepository->findOneBy(['uuid' => 'resource-1'], Argument::type('array'))->willReturn($article);

        $this->domainEventCollector->collect(Argument::that(
            static fn (object $event) => $event instanceof ArticleWorkflowTransitionRequestEvent
                && $article === $event->getArticle()
                && 'workflow_transition_request.approved' === $event->getEventType()
                && 'de' === $event->getResourceLocale()
                && ['comment' => 'Fine'] === $event->getEventContext(),
        ))->shouldBeCalledOnce();

        $this->subscriber->onWorkflowTransitionRequestAction(new WorkflowTransitionRequestActionEvent(
            new WorkflowTransitionRequest(ArticleInterface::RESOURCE_KEY, 'resource-1', 'de', 'default'),
            WorkflowTransitionRequestActionEvent::APPROVED,
            ['comment' => 'Fine'],
        ));
    }

    public function testIgnoresOtherResourceKeys(): void
    {
        $this->articleRepository->findOneBy(Argument::cetera())->shouldNotBeCalled();
        $this->domainEventCollector->collect(Argument::any())->shouldNotBeCalled();

        $this->subscriber->onWorkflowTransitionRequestAction(new WorkflowTransitionRequestActionEvent(
            new WorkflowTransitionRequest('other', 'resource-1', 'de', 'default'),
            WorkflowTransitionRequestActionEvent::APPROVED,
            [],
        ));
    }

    public function testCollectsNothingWhenTheContentIsGone(): void
    {
        $this->articleRepository->findOneBy(Argument::cetera())->willReturn(null);
        $this->domainEventCollector->collect(Argument::any())->shouldNotBeCalled();

        $this->subscriber->onWorkflowTransitionRequestAction(new WorkflowTransitionRequestActionEvent(
            new WorkflowTransitionRequest(ArticleInterface::RESOURCE_KEY, 'resource-1', 'de', 'default'),
            WorkflowTransitionRequestActionEvent::VALIDATED,
            ['approved' => 1, 'rejected' => 0],
        ));
    }
}
