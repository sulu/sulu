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

namespace Sulu\Content\Tests\Unit\Content\Application\MessageHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Application\Message\ApproveWorkflowTransitionRequestMessage;
use Sulu\Content\Application\MessageHandler\ApproveWorkflowTransitionRequestMessageHandler;
use Sulu\Content\Application\Security\WorkflowTransitionAdminAuthorizerInterface;
use Sulu\Content\Domain\Exception\MissingAuthenticatedUserException;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[CoversClass(ApproveWorkflowTransitionRequestMessageHandler::class)]
class ApproveWorkflowTransitionRequestMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    public function testInvokeThrowsWhenNoValidUser(): void
    {
        $requestId = 'some-uuid';
        $request = $this->createRequest();

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->getOneBy(['id' => $requestId])->willReturn($request);

        $tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $tokenStorage->getToken()->willReturn(null);

        $authorizer = $this->prophesize(WorkflowTransitionAdminAuthorizerInterface::class);
        $authorizer->assertCanReview(Argument::cetera())->shouldNotBeCalled();

        $handler = new ApproveWorkflowTransitionRequestMessageHandler(
            $repository->reveal(),
            $authorizer->reveal(),
            $tokenStorage->reveal(),
        );

        $this->expectException(MissingAuthenticatedUserException::class);
        $handler(new ApproveWorkflowTransitionRequestMessage($requestId));
    }

    public function testInvokeThrowsWhenUserMayNotReview(): void
    {
        $requestId = 'some-uuid';
        $request = $this->createRequest();

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->getOneBy(['id' => $requestId])->willReturn($request);

        $authorizer = $this->prophesize(WorkflowTransitionAdminAuthorizerInterface::class);
        $authorizer->assertCanReview('pages', 'res-1', 'en')->willThrow(new AccessDeniedException());

        $handler = new ApproveWorkflowTransitionRequestMessageHandler(
            $repository->reveal(),
            $authorizer->reveal(),
            $this->createTokenStorage($this->createUser(2)),
        );

        try {
            $handler(new ApproveWorkflowTransitionRequestMessage($requestId));
            $this->fail('Expected an AccessDeniedException.');
        } catch (AccessDeniedException) {
            $this->assertSame(0, $request->countUserApprovals());
        }
    }

    public function testInvokeRecordsTheApproval(): void
    {
        $requestId = 'some-uuid';
        $request = $this->createRequest();
        $reviewer = $this->createUser(2);

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->getOneBy(['id' => $requestId])->willReturn($request);

        $authorizer = $this->prophesize(WorkflowTransitionAdminAuthorizerInterface::class);
        $authorizer->assertCanReview('pages', 'res-1', 'en')->shouldBeCalled();

        $handler = new ApproveWorkflowTransitionRequestMessageHandler(
            $repository->reveal(),
            $authorizer->reveal(),
            $this->createTokenStorage($reviewer),
        );

        $result = $handler(new ApproveWorkflowTransitionRequestMessage($requestId, 'Looks good'));

        $this->assertSame($request, $result);
        $this->assertSame(1, $request->countUserApprovals());

        $decision = $request->getUserDecision($reviewer);
        $this->assertNotNull($decision);
        $this->assertTrue($decision->isApproved());
        $this->assertCount(1, $decision->getMessages());
        $this->assertSame('Looks good', $decision->getMessages()[0]->text);
    }

    public function testInvokeRecordsAnApprovalWithoutComment(): void
    {
        $requestId = 'some-uuid';
        $request = $this->createRequest();
        $reviewer = $this->createUser(2);

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->getOneBy(['id' => $requestId])->willReturn($request);

        $authorizer = $this->prophesize(WorkflowTransitionAdminAuthorizerInterface::class);
        $authorizer->assertCanReview(Argument::cetera())->shouldBeCalled();

        $handler = new ApproveWorkflowTransitionRequestMessageHandler(
            $repository->reveal(),
            $authorizer->reveal(),
            $this->createTokenStorage($reviewer),
        );

        $handler(new ApproveWorkflowTransitionRequestMessage($requestId, '   '));

        $decision = $request->getUserDecision($reviewer);
        $this->assertNotNull($decision);
        $this->assertTrue($decision->isApproved());
        $this->assertSame([], $decision->getMessages());
    }

    private function createRequest(): WorkflowTransitionRequest
    {
        $request = new WorkflowTransitionRequest('pages', 'res-1', 'en', 'default');
        $request->setCreator($this->createUser(1));

        return $request;
    }

    private function createUser(int $id): UserInterface
    {
        $user = $this->prophesize(UserInterface::class);
        $user->getId()->willReturn($id);

        return $user->reveal();
    }

    private function createTokenStorage(UserInterface $user): TokenStorageInterface
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($user);

        $tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $tokenStorage->getToken()->willReturn($token->reveal());

        return $tokenStorage->reveal();
    }
}
