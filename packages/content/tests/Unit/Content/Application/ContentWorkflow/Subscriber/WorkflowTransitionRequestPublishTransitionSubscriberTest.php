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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentWorkflow\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Application\ContentWorkflow\Subscriber\WorkflowTransitionRequestPublishTransitionSubscriber;
use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Event\WorkflowTransitionRequestSettledEvent;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\Marking;

#[CoversClass(WorkflowTransitionRequestPublishTransitionSubscriber::class)]
class WorkflowTransitionRequestPublishTransitionSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            ['workflow.content_workflow.transition.' . WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH => ['onPublish', -100]],
            WorkflowTransitionRequestPublishTransitionSubscriber::getSubscribedEvents(),
        );
    }

    public function testOnPublishSettlesTheRequestAndDispatchesTheEventWithItsData(): void
    {
        $request = new WorkflowTransitionRequest(Example::RESOURCE_KEY, '1', 'en', 'default');
        $request->setCreator($this->prophesize(UserInterface::class)->reveal());
        $request->setData(['publishAt' => '2026-12-01T10:00:00+00:00']);

        $provider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);
        $provider->findForContent(Argument::any())->willReturn($request);

        $dispatched = [];
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            WorkflowTransitionRequestSettledEvent::class,
            static function(WorkflowTransitionRequestSettledEvent $event) use (&$dispatched): void {
                $dispatched[] = $event;
            },
        );

        $subscriber = new WorkflowTransitionRequestPublishTransitionSubscriber($provider->reveal(), $eventDispatcher);
        $subscriber->onPublish(new TransitionEvent($this->createDimensionContent(), new Marking()));

        $this->assertFalse($request->isOpen());
        $this->assertCount(1, $dispatched);
        $this->assertSame(Example::RESOURCE_KEY, $dispatched[0]->getResourceKey());
        $this->assertSame('1', $dispatched[0]->getResourceId());
        $this->assertSame('en', $dispatched[0]->getLocale());
        $this->assertSame(['publishAt' => '2026-12-01T10:00:00+00:00'], $dispatched[0]->getData());
    }

    public function testOnPublishDispatchesNothingWithoutAnActiveRequest(): void
    {
        $provider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);
        $provider->findForContent(Argument::any())->willReturn(null);

        $dispatched = [];
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            WorkflowTransitionRequestSettledEvent::class,
            static function(WorkflowTransitionRequestSettledEvent $event) use (&$dispatched): void {
                $dispatched[] = $event;
            },
        );

        $subscriber = new WorkflowTransitionRequestPublishTransitionSubscriber($provider->reveal(), $eventDispatcher);
        $subscriber->onPublish(new TransitionEvent($this->createDimensionContent(), new Marking()));

        $this->assertSame([], $dispatched);
    }

    public function testOnPublishIgnoresForeignSubjects(): void
    {
        $provider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);
        $provider->findForContent(Argument::cetera())->shouldNotBeCalled();

        $subscriber = new WorkflowTransitionRequestPublishTransitionSubscriber($provider->reveal(), new EventDispatcher());
        $subscriber->onPublish(new TransitionEvent(new \stdClass(), new Marking()));
    }

    private function createDimensionContent(): ExampleDimensionContent
    {
        $example = new Example();
        (new \ReflectionClass($example))->getProperty('id')->setValue($example, '1');

        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setLocale('en');

        return $dimensionContent;
    }
}
