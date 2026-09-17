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
use Sulu\Content\Application\ContentWorkflow\Subscriber\WorkflowTransitionRequestCancelTransitionSubscriber;
use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\Marking;

#[CoversClass(WorkflowTransitionRequestCancelTransitionSubscriber::class)]
class WorkflowTransitionRequestCancelTransitionSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public function testGetSubscribedEvents(): void
    {
        $prefix = 'workflow.content_workflow.transition.';

        $this->assertSame(
            [
                $prefix . WorkflowInterface::WORKFLOW_TRANSITION_CANCEL_REVIEW => 'onLeaveReview',
                $prefix . WorkflowInterface::WORKFLOW_TRANSITION_CANCEL_REVIEW_DRAFT => 'onLeaveReview',
                $prefix . WorkflowInterface::WORKFLOW_TRANSITION_REJECT => 'onLeaveReview',
                $prefix . WorkflowInterface::WORKFLOW_TRANSITION_REJECT_DRAFT => 'onLeaveReview',
            ],
            WorkflowTransitionRequestCancelTransitionSubscriber::getSubscribedEvents(),
        );
    }

    /**
     * Every transition out of a review place closes the request: leaving it open would lock the
     * content with no transition left to free it.
     */
    public function testOnLeaveReviewClosesTheRequest(): void
    {
        $request = new WorkflowTransitionRequest(Example::RESOURCE_KEY, '1', 'en', 'default');
        $request->setCreator($this->prophesize(UserInterface::class)->reveal());

        $provider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);
        $provider->findForContent(Argument::any())->willReturn($request);

        $subscriber = new WorkflowTransitionRequestCancelTransitionSubscriber($provider->reveal());
        $subscriber->onLeaveReview(new TransitionEvent($this->createDimensionContent(), new Marking()));

        $this->assertFalse($request->isOpen());
    }

    public function testOnLeaveReviewDoesNothingWithoutAnActiveRequest(): void
    {
        $provider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);
        $provider->findForContent(Argument::any())->willReturn(null)->shouldBeCalledOnce();

        $subscriber = new WorkflowTransitionRequestCancelTransitionSubscriber($provider->reveal());
        $subscriber->onLeaveReview(new TransitionEvent($this->createDimensionContent(), new Marking()));
    }

    public function testOnLeaveReviewIgnoresForeignSubjects(): void
    {
        $provider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);
        $provider->findForContent(Argument::cetera())->shouldNotBeCalled();

        $subscriber = new WorkflowTransitionRequestCancelTransitionSubscriber($provider->reveal());
        $subscriber->onLeaveReview(new TransitionEvent(new \stdClass(), new Marking()));
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
