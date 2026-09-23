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

namespace Sulu\Content\Tests\Unit\Content\Application\WorkflowTransitionRequest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflow;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowRegistryInterface;
use Sulu\Content\Application\RequestWorkflow\Validator\RequestWorkflowValidatorInterface;
use Sulu\Content\Application\RequestWorkflow\WorkflowTransitionRequestStatusResolverInterface;
use Sulu\Content\Application\Security\WorkflowTransitionAdminAuthorizerInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\WorkflowTransitionRequestViewFactory;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionMessage;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionStatusEnum;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;

#[CoversClass(WorkflowTransitionRequestViewFactory::class)]
class WorkflowTransitionRequestViewFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testChecksAndApprovalsAreSerializedAsTwoLists(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('unpublished_references');
        $request->addValidatorDecision('llm_review');
        $this->settle($request, 'unpublished_references', WorkflowTransitionRequestDecisionStatusEnum::APPROVED);
        $this->settle($request, 'llm_review', WorkflowTransitionRequestDecisionStatusEnum::REJECTED, 'The intro contradicts the headline.');
        $request->addApproval($this->user(2, 'Reviewer A'), WorkflowTransitionRequestDecisionMessage::text('Fine by me'));
        $request->addRejection($this->user(3, 'Reviewer B'), WorkflowTransitionRequestDecisionMessage::text('Please fix the intro'));

        $view = $this->createFactory(3, ['llm_review'])->build($request);

        $this->assertSame('pending', $view['status']);
        $this->assertSame(
            ['required' => 3, 'approved' => 1, 'rejected' => 1],
            $view['approvalProgress'],
            'The progress counts people only, the checks report separately.',
        );

        /** @var list<array<string, mixed>> $checks */
        $checks = $view['checks'];
        $this->assertSame(
            [
                ['unpublished_references', 'approved', false],
                ['llm_review', 'rejected', true],
            ],
            \array_map(
                static fn (array $check) => [$check['validatorKey'], $check['status'], $check['required']],
                $checks,
            ),
        );
        $this->assertSame([['key' => null, 'parameters' => [], 'text' => 'The intro contradicts the headline.']], $checks[1]['messages']);

        /** @var list<array<string, mixed>> $approvals */
        $approvals = $view['approvals'];
        $this->assertSame(
            ['approved', 'rejected'],
            \array_column($approvals, 'status'),
        );
        $this->assertSame(['id' => 2, 'fullName' => 'Reviewer A'], $approvals[0]['reviewer']);
        $this->assertNotNull($approvals[0]['decidedAt']);
    }

    public function testPendingCheckCarriesNoDecision(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('unpublished_references');

        $view = $this->createFactory()->build($request);

        /** @var list<array<string, mixed>> $checks */
        $checks = $view['checks'];
        $this->assertCount(1, $checks);
        $this->assertSame('pending', $checks[0]['status']);
        $this->assertSame([], $checks[0]['messages']);
        $this->assertNull($checks[0]['decidedAt']);
        $this->assertSame([], $view['approvals']);
        $this->assertSame(['required' => 1, 'approved' => 0, 'rejected' => 0], $view['approvalProgress']);
        $this->assertSame(['id' => 1, 'fullName' => 'Creator'], $view['createdBy']);
    }

    public function testViewCarriesWhatTheUserMayDo(): void
    {
        $permissions = ['cancel' => true, 'publish' => false, 'retry' => true, 'review' => false];

        $view = $this->createFactory(1, [], $permissions)->build($this->createRequest());

        $this->assertSame(
            $permissions,
            $view['permissions'],
            'Content without object security carries no permissions, so the request answers for it.',
        );
    }

    /**
     * @param list<string> $requiredValidatorKeys
     * @param array{cancel: bool, publish: bool, retry: bool, review: bool}|null $permissions
     */
    private function createFactory(
        int $requiredUserApprovals = 1,
        array $requiredValidatorKeys = [],
        ?array $permissions = null,
    ): WorkflowTransitionRequestViewFactory {
        $registry = $this->prophesize(RequestWorkflowRegistryInterface::class);
        $registry->has(WorkflowTransitionRequest::DEFAULT_WORKFLOW_NAME)->willReturn(true);
        $registry->get(WorkflowTransitionRequest::DEFAULT_WORKFLOW_NAME)->willReturn(new RequestWorkflow(
            WorkflowTransitionRequest::DEFAULT_WORKFLOW_NAME,
            \array_fill_keys($requiredValidatorKeys, ['validator' => $this->prophesize(RequestWorkflowValidatorInterface::class)->reveal(), 'config' => [], 'required' => true]),
            $requiredUserApprovals,
        ));

        $statusResolver = $this->prophesize(WorkflowTransitionRequestStatusResolverInterface::class);
        $statusResolver->resolve(Argument::type(WorkflowTransitionRequest::class))
            ->willReturn(WorkflowTransitionRequestStatusEnum::PENDING);

        $authorizer = $this->prophesize(WorkflowTransitionAdminAuthorizerInterface::class);
        $authorizer->getPermissions('pages', Argument::type('string'), 'en')
            ->willReturn($permissions ?? ['cancel' => true, 'publish' => true, 'retry' => true, 'review' => true]);

        return new WorkflowTransitionRequestViewFactory(
            $registry->reveal(),
            $statusResolver->reveal(),
            $authorizer->reveal(),
        );
    }

    private function settle(
        WorkflowTransitionRequest $request,
        string $validatorKey,
        WorkflowTransitionRequestDecisionStatusEnum $status,
        ?string $text = null,
    ): void {
        $request->getValidatorDecision($validatorKey)?->settle(
            $status,
            null === $text ? [] : [WorkflowTransitionRequestDecisionMessage::text($text)],
            new \DateTimeImmutable(),
        );
    }

    private function createRequest(): WorkflowTransitionRequest
    {
        $request = new WorkflowTransitionRequest(
            'pages',
            'test-id',
            'en',
            WorkflowTransitionRequest::DEFAULT_WORKFLOW_NAME,
        );
        $request->setCreator($this->user(1, 'Creator'));

        return $request;
    }

    private function user(int $id, string $fullName): UserInterface
    {
        $user = $this->prophesize(UserInterface::class);
        $user->getId()->willReturn($id);
        $user->getFullName()->willReturn($fullName);

        return $user->reveal();
    }
}
