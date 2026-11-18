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
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Application\ContentWorkflow\Subscriber\WorkflowTransitionRequestPublishReplacementSubscriber;
use Sulu\Content\Application\WorkflowTransitionRequest\Action\WorkflowTransitionRequestActionRegistryInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Content\Tests\Unit\Content\Application\WorkflowTransitionRequest\Action\StubWorkflowTransitionRequestAction;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;

#[CoversClass(WorkflowTransitionRequestPublishReplacementSubscriber::class)]
class WorkflowTransitionRequestPublishReplacementSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            ['workflow.content_workflow.guard.' . WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH => 'onPublish'],
            WorkflowTransitionRequestPublishReplacementSubscriber::getSubscribedEvents(),
        );
    }

    public function testBlocksAPublishByAUserWhileAnActionHasTakenItOver(): void
    {
        $guardEvent = $this->createGuardEvent();

        $this->createSubscriber(actions: [new StubWorkflowTransitionRequestAction(replacesPublish: true)])
            ->onPublish($guardEvent);

        $this->assertTrue($guardEvent->isBlocked());

        $blockers = \iterator_to_array($guardEvent->getTransitionBlockerList());
        $this->assertCount(1, $blockers);
        $this->assertSame(ContentWorkflowInterface::BLOCKER_CODE_EXCEPTION, $blockers[0]->getCode());
        $this->assertInstanceOf(
            AccessDeniedException::class,
            $blockers[0]->getParameters()[ContentWorkflowInterface::BLOCKER_EXCEPTION_PARAMETER],
        );
        $this->assertStringContainsString('"stub"', $blockers[0]->getMessage());
    }

    /**
     * The scheduled task publishes without a token, and it is the action that asked for exactly that.
     */
    public function testLetsAPublishWithoutAUserPass(): void
    {
        $guardEvent = $this->createGuardEvent();

        $this->createSubscriber(actions: [new StubWorkflowTransitionRequestAction(replacesPublish: true)], user: false)
            ->onPublish($guardEvent);

        $this->assertFalse($guardEvent->isBlocked());
    }

    public function testLetsAPublishPassWhenNoActionTakesItOver(): void
    {
        $guardEvent = $this->createGuardEvent();

        $this->createSubscriber(actions: [new StubWorkflowTransitionRequestAction()])->onPublish($guardEvent);

        $this->assertFalse($guardEvent->isBlocked());
    }

    public function testLetsAPublishPassWithoutAnOpenRequest(): void
    {
        $guardEvent = $this->createGuardEvent();

        $this->createSubscriber(
            actions: [new StubWorkflowTransitionRequestAction(replacesPublish: true)],
            request: false,
        )->onPublish($guardEvent);

        $this->assertFalse($guardEvent->isBlocked());
    }

    public function testIgnoresASubjectThatIsNoContent(): void
    {
        $guardEvent = new GuardEvent(new \stdClass(), new Marking(), new Transition('publish', 'a', 'b'));

        $this->createSubscriber(actions: [new StubWorkflowTransitionRequestAction(replacesPublish: true)])
            ->onPublish($guardEvent);

        $this->assertFalse($guardEvent->isBlocked());
    }

    /**
     * @param list<StubWorkflowTransitionRequestAction> $actions
     */
    private function createSubscriber(
        array $actions,
        bool $user = true,
        bool $request = true,
    ): WorkflowTransitionRequestPublishReplacementSubscriber {
        $provider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);
        $provider->findForContent(Argument::any())->willReturn(
            $request ? new WorkflowTransitionRequest(Example::RESOURCE_KEY, '1', 'en', 'review') : null,
        );

        $registry = $this->prophesize(WorkflowTransitionRequestActionRegistryInterface::class);
        $registry->all()->willReturn($actions);

        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($user ? $this->prophesize(UserInterface::class)->reveal() : null);
        $tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $tokenStorage->getToken()->willReturn($token->reveal());

        return new WorkflowTransitionRequestPublishReplacementSubscriber(
            $provider->reveal(),
            $registry->reveal(),
            $tokenStorage->reveal(),
        );
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
