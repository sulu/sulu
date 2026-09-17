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
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Application\ContentWorkflow\Subscriber\WorkflowTransitionAuthorizationSubscriber;
use Sulu\Content\Application\Security\WorkflowTransitionAdminAuthorizerInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;

#[CoversClass(WorkflowTransitionAuthorizationSubscriber::class)]
class WorkflowTransitionAuthorizationSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public function testGetSubscribedEvents(): void
    {
        $prefix = 'workflow.content_workflow.guard.';

        $this->assertSame(
            [
                $prefix . WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH => 'onPublish',
                $prefix . WorkflowInterface::WORKFLOW_TRANSITION_REJECT => 'onReject',
                $prefix . WorkflowInterface::WORKFLOW_TRANSITION_REJECT_DRAFT => 'onReject',
                $prefix . WorkflowInterface::WORKFLOW_TRANSITION_CANCEL_REVIEW => 'onCancelReview',
                $prefix . WorkflowInterface::WORKFLOW_TRANSITION_CANCEL_REVIEW_DRAFT => 'onCancelReview',
            ],
            WorkflowTransitionAuthorizationSubscriber::getSubscribedEvents(),
        );
    }

    /**
     * A refused permission blocks rather than throws: the workflow also asks its guards to list the
     * transitions a subject could take, and a throw there would escape a question nobody asked.
     */
    public function testOnPublishBlocksWhenTheUserMayNotPublish(): void
    {
        $exception = new AccessDeniedException('nope');

        $authorizer = $this->prophesize(WorkflowTransitionAdminAuthorizerInterface::class);
        $authorizer->assertCanPublish(Example::RESOURCE_KEY, '1', 'en')->willThrow($exception);

        $guardEvent = $this->createGuardEvent();
        (new WorkflowTransitionAuthorizationSubscriber($authorizer->reveal()))->onPublish($guardEvent);

        $this->assertTrue($guardEvent->isBlocked());

        $blockers = \iterator_to_array($guardEvent->getTransitionBlockerList());
        $this->assertCount(1, $blockers);
        $this->assertSame(ContentWorkflowInterface::BLOCKER_CODE_EXCEPTION, $blockers[0]->getCode());
        $this->assertSame(
            $exception,
            $blockers[0]->getParameters()[ContentWorkflowInterface::BLOCKER_EXCEPTION_PARAMETER],
        );
    }

    public function testOnPublishLetsAnAllowedTransitionThrough(): void
    {
        $authorizer = $this->prophesize(WorkflowTransitionAdminAuthorizerInterface::class);
        $authorizer->assertCanPublish(Example::RESOURCE_KEY, '1', 'en')->shouldBeCalled();

        $guardEvent = $this->createGuardEvent();
        (new WorkflowTransitionAuthorizationSubscriber($authorizer->reveal()))->onPublish($guardEvent);

        $this->assertFalse($guardEvent->isBlocked());
    }

    public function testOnRejectAsksForTheReviewPermission(): void
    {
        $authorizer = $this->prophesize(WorkflowTransitionAdminAuthorizerInterface::class);
        $authorizer->assertCanReview(Example::RESOURCE_KEY, '1', 'en')->willThrow(new AccessDeniedException());

        $guardEvent = $this->createGuardEvent();
        (new WorkflowTransitionAuthorizationSubscriber($authorizer->reveal()))->onReject($guardEvent);

        $this->assertTrue($guardEvent->isBlocked());
    }

    /**
     * Asked from a guard and not from the transition, so `Workflow::can()` answers the same thing
     * the call would: the admin hides a cancel it would refuse instead of offering a button that fails.
     */
    public function testOnCancelReviewBlocksWhenTheRequestMayNotBeWithdrawn(): void
    {
        $authorizer = $this->prophesize(WorkflowTransitionAdminAuthorizerInterface::class);
        $authorizer->assertCanCancelReview(Example::RESOURCE_KEY, '1', 'en')->willThrow(new AccessDeniedException());

        $guardEvent = $this->createGuardEvent();
        (new WorkflowTransitionAuthorizationSubscriber($authorizer->reveal()))->onCancelReview($guardEvent);

        $this->assertTrue($guardEvent->isBlocked());
    }

    public function testForeignSubjectsAreIgnored(): void
    {
        $authorizer = $this->prophesize(WorkflowTransitionAdminAuthorizerInterface::class);
        $authorizer->assertCanPublish(Argument::cetera())->shouldNotBeCalled();

        $guardEvent = new GuardEvent(new \stdClass(), new Marking(), new Transition('publish', 'a', 'b'));
        (new WorkflowTransitionAuthorizationSubscriber($authorizer->reveal()))->onPublish($guardEvent);

        $this->assertFalse($guardEvent->isBlocked());
    }

    /**
     * @return GuardEvent<ExampleDimensionContent>
     */
    private function createGuardEvent(): GuardEvent
    {
        $example = new Example();
        (new \ReflectionClass($example))->getProperty('id')->setValue($example, '1');

        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setLocale('en');

        return new GuardEvent($dimensionContent, new Marking(), new Transition('publish', 'a', 'b'));
    }
}
