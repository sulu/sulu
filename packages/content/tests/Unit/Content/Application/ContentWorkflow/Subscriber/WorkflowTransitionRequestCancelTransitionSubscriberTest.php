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
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Content\Application\ContentWorkflow\Subscriber\WorkflowTransitionRequestCancelTransitionSubscriber;
use Sulu\Content\Application\Security\WorkflowTransitionRequestSecurityContextResolverInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Domain\Exception\WorkflowTransitionRequestCancelNotAllowedException;
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
                $prefix . WorkflowInterface::WORKFLOW_TRANSITION_CANCEL_REVIEW => 'onCancelReview',
                $prefix . WorkflowInterface::WORKFLOW_TRANSITION_CANCEL_REVIEW_DRAFT => 'onCancelReview',
                $prefix . WorkflowInterface::WORKFLOW_TRANSITION_REJECT => 'onReject',
                $prefix . WorkflowInterface::WORKFLOW_TRANSITION_REJECT_DRAFT => 'onReject',
            ],
            WorkflowTransitionRequestCancelTransitionSubscriber::getSubscribedEvents(),
        );
    }

    /**
     * The content-level reject takes the content out of review, so the request cannot stay open
     * behind it: that would leave the content locked with no transition left to free it.
     */
    public function testOnRejectClosesTheRequest(): void
    {
        $request = new WorkflowTransitionRequest(Example::RESOURCE_KEY, '1', 'en', 'default');
        $request->setCreator($this->prophesize(UserInterface::class)->reveal());

        $securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $securityChecker->hasPermission(Argument::cetera())->shouldNotBeCalled();

        $this->createSubscriber($request, $securityChecker)
            ->onReject(new TransitionEvent($this->createDimensionContent(), new Marking()));

        $this->assertFalse($request->isOpen());
    }

    public function testOnRejectDoesNothingWithoutAnActiveRequest(): void
    {
        $provider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);
        $provider->findForContent(Argument::any())->willReturn(null)->shouldBeCalledOnce();

        $subscriber = new WorkflowTransitionRequestCancelTransitionSubscriber(
            $provider->reveal(),
            $this->prophesize(SecurityCheckerInterface::class)->reveal(),
            $this->prophesize(WorkflowTransitionRequestSecurityContextResolverInterface::class)->reveal(),
        );

        $subscriber->onReject(new TransitionEvent($this->createDimensionContent(), new Marking()));
    }

    public function testOnCancelReviewClosesTheRequestWithTheEditPermission(): void
    {
        $request = new WorkflowTransitionRequest(Example::RESOURCE_KEY, '1', 'en', 'default');
        $request->setCreator($this->prophesize(UserInterface::class)->reveal());

        $securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $securityChecker->hasPermission($this->conditionOn('sulu.example'), PermissionTypes::EDIT)->willReturn(true);

        $this->createSubscriber($request, $securityChecker)
            ->onCancelReview(new TransitionEvent($this->createDimensionContent(), new Marking()));

        $this->assertFalse($request->isOpen());
    }

    public function testOnCancelReviewIsRefusedWithoutTheEditPermission(): void
    {
        $request = new WorkflowTransitionRequest(Example::RESOURCE_KEY, '1', 'en', 'default');
        $request->setCreator($this->prophesize(UserInterface::class)->reveal());

        $securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $securityChecker->hasPermission($this->conditionOn('sulu.example'), PermissionTypes::EDIT)->willReturn(false);

        $subscriber = $this->createSubscriber($request, $securityChecker);

        $this->expectException(WorkflowTransitionRequestCancelNotAllowedException::class);

        try {
            $subscriber->onCancelReview(new TransitionEvent($this->createDimensionContent(), new Marking()));
        } finally {
            $this->assertTrue($request->isOpen(), 'A refused cancel must leave the request untouched.');
        }
    }

    public function testOnCancelReviewDoesNothingWithoutAnActiveRequest(): void
    {
        $provider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);
        $provider->findForContent(Argument::any())->willReturn(null);

        $securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $securityChecker->hasPermission(Argument::cetera())->shouldNotBeCalled();

        $subscriber = new WorkflowTransitionRequestCancelTransitionSubscriber(
            $provider->reveal(),
            $securityChecker->reveal(),
            $this->prophesize(WorkflowTransitionRequestSecurityContextResolverInterface::class)->reveal(),
        );

        $subscriber->onCancelReview(new TransitionEvent($this->createDimensionContent(), new Marking()));
    }

    /**
     * @return \Prophecy\Argument\Token\CallbackToken
     */
    private function conditionOn(string $context)
    {
        return Argument::that(
            static fn ($condition) => $condition instanceof SecurityCondition
                && $context === $condition->getSecurityContext()
                && 'en' === $condition->getLocale()
        );
    }

    /**
     * @param \Prophecy\Prophecy\ObjectProphecy<SecurityCheckerInterface> $securityChecker
     */
    private function createSubscriber(
        WorkflowTransitionRequest $request,
        $securityChecker,
    ): WorkflowTransitionRequestCancelTransitionSubscriber {
        $provider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);
        $provider->findForContent(Argument::any())->willReturn($request);

        $securityContextResolver = $this->prophesize(WorkflowTransitionRequestSecurityContextResolverInterface::class);
        $securityContextResolver->resolve(Example::RESOURCE_KEY, '1', 'en')
            ->willReturn(new SecurityCondition('sulu.example', 'en'));

        return new WorkflowTransitionRequestCancelTransitionSubscriber(
            $provider->reveal(),
            $securityChecker->reveal(),
            $securityContextResolver->reveal(),
        );
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
