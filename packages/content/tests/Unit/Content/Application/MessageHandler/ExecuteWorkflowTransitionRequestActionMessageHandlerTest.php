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
use Sulu\Content\Application\Message\ExecuteWorkflowTransitionRequestActionMessage;
use Sulu\Content\Application\MessageHandler\ExecuteWorkflowTransitionRequestActionMessageHandler;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Content\Application\Security\WorkflowTransitionAdminAuthorizerInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Action\WorkflowTransitionRequestActionRegistryInterface;
use Sulu\Content\Domain\Exception\WorkflowTransitionRequestActionNotAvailableException;
use Sulu\Content\Domain\Exception\WorkflowTransitionRequestClosedException;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Tests\Unit\Content\Application\WorkflowTransitionRequest\Action\StubWorkflowTransitionRequestAction;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[CoversClass(ExecuteWorkflowTransitionRequestActionMessageHandler::class)]
class ExecuteWorkflowTransitionRequestActionMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    public function testExecutesTheActionOnTheRequest(): void
    {
        $request = new WorkflowTransitionRequest('pages', '1', 'en', 'default');
        $action = new StubWorkflowTransitionRequestAction();

        $result = $this->createHandler($request, $action)(
            new ExecuteWorkflowTransitionRequestActionMessage($request->getId(), 'stub'),
        );

        $this->assertSame($request, $result);
        $this->assertSame([$request], $action->executed);
    }

    public function testRefusesAClosedRequest(): void
    {
        $request = new WorkflowTransitionRequest('pages', '1', 'en', 'default');
        $request->cancel();
        $action = new StubWorkflowTransitionRequestAction();

        try {
            $this->createHandler($request, $action)(
                new ExecuteWorkflowTransitionRequestActionMessage($request->getId(), 'stub'),
            );
            $this->fail('A closed request must refuse the action.');
        } catch (WorkflowTransitionRequestClosedException) {
            $this->assertSame([], $action->executed);
        }
    }

    public function testRefusesAnActionThatDoesNotFitTheRequest(): void
    {
        $request = new WorkflowTransitionRequest('pages', '1', 'en', 'default');
        $action = new StubWorkflowTransitionRequestAction(available: false);

        try {
            $this->createHandler($request, $action)(
                new ExecuteWorkflowTransitionRequestActionMessage($request->getId(), 'stub'),
            );
            $this->fail('An unavailable action must not run.');
        } catch (WorkflowTransitionRequestActionNotAvailableException) {
            $this->assertSame([], $action->executed);
        }
    }

    public function testRefusesAnUnknownAction(): void
    {
        $request = new WorkflowTransitionRequest('pages', '1', 'en', 'default');

        $this->expectException(WorkflowTransitionRequestActionNotAvailableException::class);

        $this->createHandler($request, null)(
            new ExecuteWorkflowTransitionRequestActionMessage($request->getId(), 'unknown'),
        );
    }

    /**
     * The message is on the public bus, so code that dispatches it skips the controller's check.
     */
    public function testRefusesAUserWithoutThePermissionOfTheAction(): void
    {
        $request = new WorkflowTransitionRequest('pages', '1', 'en', 'default');
        $action = new StubWorkflowTransitionRequestAction(permission: PermissionTypes::LIVE);
        $authorizer = $this->prophesize(WorkflowTransitionAdminAuthorizerInterface::class);
        $authorizer->assertHasPermission('pages', '1', 'en', PermissionTypes::LIVE)->willThrow(new AccessDeniedException('no'));

        try {
            $this->createHandler($request, $action, $authorizer)(
                new ExecuteWorkflowTransitionRequestActionMessage($request->getId(), 'stub'),
            );
            $this->fail('A user without the permission of the action must not run it.');
        } catch (AccessDeniedException) {
            $this->assertSame([], $action->executed);
        }
    }

    /**
     * @param \Prophecy\Prophecy\ObjectProphecy<WorkflowTransitionAdminAuthorizerInterface>|null $authorizer
     */
    private function createHandler(
        WorkflowTransitionRequest $request,
        ?StubWorkflowTransitionRequestAction $action,
        $authorizer = null,
    ): ExecuteWorkflowTransitionRequestActionMessageHandler {
        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->getOneBy(['id' => $request->getId()])->willReturn($request);

        $registry = $this->prophesize(WorkflowTransitionRequestActionRegistryInterface::class);
        $registry->get('stub')->willReturn($action);
        $registry->get('unknown')->willReturn(null);

        return new ExecuteWorkflowTransitionRequestActionMessageHandler(
            $repository->reveal(),
            $registry->reveal(),
            ($authorizer ?? $this->prophesize(WorkflowTransitionAdminAuthorizerInterface::class))->reveal(),
        );
    }
}
