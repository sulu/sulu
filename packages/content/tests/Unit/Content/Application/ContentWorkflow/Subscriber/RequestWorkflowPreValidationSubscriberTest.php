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
use Sulu\Content\Application\ContentWorkflow\Subscriber\RequestWorkflowPreValidationSubscriber;
use Sulu\Content\Application\RequestWorkflow\PreValidator\PreValidationContext;
use Sulu\Content\Application\RequestWorkflow\PreValidator\PreValidationFailure;
use Sulu\Content\Application\RequestWorkflow\PreValidator\RequestWorkflowPreValidatorInterface;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflow;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowResolverInterface;
use Sulu\Content\Domain\Exception\WorkflowTransitionRequestPreValidationFailedException;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\Marking;

#[CoversClass(RequestWorkflowPreValidationSubscriber::class)]
class RequestWorkflowPreValidationSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * Publishing must not walk around the pre-validators, so they hang on the publish transitions
     * as well as on the request ones.
     */
    public function testGetSubscribedEvents(): void
    {
        $prefix = 'workflow.content_workflow.transition.';

        $this->assertSame(
            [
                $prefix . WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW => ['onTransition', 200],
                $prefix . WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT => ['onTransition', 200],
                $prefix . WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH => ['onTransition', 200],
            ],
            RequestWorkflowPreValidationSubscriber::getSubscribedEvents(),
        );
    }

    public function testEveryCheckContributesItsOwnEntryIncludingThePassingOnes(): void
    {
        $dimensionContent = $this->createDimensionContent();

        $subscriber = $this->createSubscriber($dimensionContent, new RequestWorkflow('review', [], 1, [], [
            'seo_required' => [
                'pre_validator' => $this->createPreValidator([new PreValidationFailure('seo.missing', ['fields' => 'title'])]),
                'config' => [],
            ],
            'excerpt_required' => [
                'pre_validator' => $this->createPreValidator([]),
                'config' => [],
            ],
        ]));

        try {
            $subscriber->onTransition(new TransitionEvent($dimensionContent, new Marking()));
            $this->fail('Expected the pre-validators to abort the transition.');
        } catch (WorkflowTransitionRequestPreValidationFailedException $exception) {
            $this->assertSame(
                [
                    [
                        'key' => 'seo_required',
                        'passed' => false,
                        'failures' => [['messageKey' => 'seo.missing', 'messageParameters' => ['fields' => 'title']]],
                    ],
                    ['key' => 'excerpt_required', 'passed' => true, 'failures' => []],
                ],
                $exception->getPreValidationResults(),
                'A passing check keeps its row, so the overlay can show what is already done.',
            );
        }
    }

    public function testPassingPreValidatorsLetTheTransitionThrough(): void
    {
        $dimensionContent = $this->createDimensionContent();

        $subscriber = $this->createSubscriber($dimensionContent, new RequestWorkflow('review', [], 1, [], [
            'seo_required' => ['pre_validator' => $this->createPreValidator([]), 'config' => []],
        ]));

        $subscriber->onTransition(new TransitionEvent($dimensionContent, new Marking()));

        $this->expectNotToPerformAssertions();
    }

    /**
     * Content outside any request workflow has no pre-validators, so publishing it is untouched.
     */
    public function testContentWithoutAWorkflowIsLetThrough(): void
    {
        $dimensionContent = $this->createDimensionContent();

        $this->createSubscriber($dimensionContent, null)
            ->onTransition(new TransitionEvent($dimensionContent, new Marking()));

        $this->expectNotToPerformAssertions();
    }

    /**
     * @param list<PreValidationFailure> $failures
     */
    private function createPreValidator(array $failures): RequestWorkflowPreValidatorInterface
    {
        $preValidator = $this->prophesize(RequestWorkflowPreValidatorInterface::class);
        $preValidator->check(Argument::type(PreValidationContext::class))->willReturn($failures);

        return $preValidator->reveal();
    }

    /**
     * A command, a fixture or a consumer publishes with no user, so there is nobody to tell what is
     * missing. `sulu:page:initialize` publishes an empty homepage this way.
     */
    public function testASystemCallWithoutAnAuthenticatedUserSkipsPreValidation(): void
    {
        $dimensionContent = $this->createDimensionContent();

        $resolver = $this->prophesize(RequestWorkflowResolverInterface::class);
        $resolver->resolveForContent(Argument::any())->shouldNotBeCalled();

        $tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $tokenStorage->getToken()->willReturn(null);

        $subscriber = new RequestWorkflowPreValidationSubscriber($resolver->reveal(), $tokenStorage->reveal());
        $subscriber->onTransition(new TransitionEvent($dimensionContent, new Marking()));
    }

    private function createSubscriber(
        ExampleDimensionContent $dimensionContent,
        ?RequestWorkflow $workflow,
    ): RequestWorkflowPreValidationSubscriber {
        $resolver = $this->prophesize(RequestWorkflowResolverInterface::class);
        $resolver->resolveForContent($dimensionContent)->willReturn($workflow);

        return new RequestWorkflowPreValidationSubscriber($resolver->reveal(), $this->authenticatedTokenStorage());
    }

    /**
     * @return TokenStorageInterface
     */
    private function authenticatedTokenStorage()
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($this->prophesize(UserInterface::class)->reveal());

        $tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $tokenStorage->getToken()->willReturn($token->reveal());

        return $tokenStorage->reveal();
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
