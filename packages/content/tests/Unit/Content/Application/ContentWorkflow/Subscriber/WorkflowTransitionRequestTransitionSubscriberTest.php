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
use Sulu\Content\Application\ContentWorkflow\Subscriber\WorkflowTransitionRequestTransitionSubscriber;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflow;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowResolverInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Domain\Exception\MissingAuthenticatedUserException;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\Marking;

#[CoversClass(WorkflowTransitionRequestTransitionSubscriber::class)]
class WorkflowTransitionRequestTransitionSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [
                'workflow.content_workflow.transition.' . WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW => 'onRequestForReview',
                'workflow.content_workflow.transition.' . WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT => 'onRequestForReview',
            ],
            WorkflowTransitionRequestTransitionSubscriber::getSubscribedEvents(),
        );
    }

    /**
     * The admin always has a token, so only a CLI or a message consumer can reach this. The request
     * needs a creator, because only the creator may cancel it again.
     */
    public function testOnRequestForReviewThrowsWithoutAnAuthenticatedUser(): void
    {
        $dimensionContent = $this->createDimensionContent($this->createExample('1'), 'en');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->add(Argument::any())->shouldNotBeCalled();

        $provider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);
        $provider->find(Argument::cetera())->willReturn(null);

        $tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $tokenStorage->getToken()->willReturn(null);

        $resolver = $this->prophesize(RequestWorkflowResolverInterface::class);
        $resolver->resolveForContent($dimensionContent)->willReturn(new RequestWorkflow('default', [], 1));

        $subscriber = new WorkflowTransitionRequestTransitionSubscriber(
            $repository->reveal(),
            $provider->reveal(),
            $tokenStorage->reveal(),
            $resolver->reveal(),
            $this->createMessageBus(),
        );

        $this->expectException(MissingAuthenticatedUserException::class);

        $subscriber->onRequestForReview(new TransitionEvent($dimensionContent, new Marking()));
    }

    private function createMessageBus(): MessageBusInterface
    {
        $messageBus = $this->prophesize(MessageBusInterface::class);
        $messageBus->dispatch(Argument::any(), Argument::any())
            ->will(static function(array $args) {
                /** @var object $message */
                $message = $args[0];

                return new Envelope($message);
            });

        return $messageBus->reveal();
    }

    private function createExample(string $id): Example
    {
        $example = new Example();
        $reflection = new \ReflectionClass($example);
        $property = $reflection->getProperty('id');
        $property->setValue($example, $id);

        return $example;
    }

    private function createDimensionContent(Example $example, string $locale): ExampleDimensionContent
    {
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setLocale($locale);

        return $dimensionContent;
    }
}
