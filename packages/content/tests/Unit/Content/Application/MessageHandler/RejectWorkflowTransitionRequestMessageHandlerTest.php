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
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Application\Message\RejectWorkflowTransitionRequestMessage;
use Sulu\Content\Application\MessageHandler\RejectWorkflowTransitionRequestMessageHandler;
use Sulu\Content\Domain\Exception\MissingAuthenticatedUserException;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[CoversClass(RejectWorkflowTransitionRequestMessageHandler::class)]
class RejectWorkflowTransitionRequestMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    public function testInvokeThrowsWhenNoValidUser(): void
    {
        $requestId = 'some-uuid';

        $creator = $this->prophesize(UserInterface::class)->reveal();
        $request = new WorkflowTransitionRequest('pages', 'res-1', 'en', 'default');
        $request->setCreator($creator);

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->getOneBy(['id' => $requestId])->willReturn($request);

        $tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $tokenStorage->getToken()->willReturn(null);

        $handler = new RejectWorkflowTransitionRequestMessageHandler(
            $repository->reveal(),
            $tokenStorage->reveal(),
        );

        $this->expectException(MissingAuthenticatedUserException::class);
        $handler(new RejectWorkflowTransitionRequestMessage($requestId, 'Needs rework'));
    }
}
