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

namespace Sulu\Content\Tests\Unit\Content\Domain\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflow;
use Sulu\Content\Domain\Exception\SelfReviewNotAllowedException;
use Sulu\Content\Domain\Exception\WorkflowTransitionRequestClosedException;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestApprovalStatusEnum;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestLifecycleEnum;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;

#[CoversClass(WorkflowTransitionRequest::class)]
#[CoversClass(WorkflowTransitionRequestLifecycleEnum::class)]
#[CoversClass(WorkflowTransitionRequestStatusEnum::class)]
class WorkflowTransitionRequestTest extends TestCase
{
    use ProphecyTrait;

    public function testConstructInitializesOpenRequest(): void
    {
        $request = $this->createRequest();

        $this->assertNotSame('', $request->getId());
        $this->assertSame(RequestWorkflow::DEFAULT_NAME, $request->getWorkflowName());
        $this->assertSame(WorkflowTransitionRequestStatusEnum::PENDING, $request->getStatus());
        $this->assertSame('pages:4d3e0d90-4cc8-46c4-a6dc-9f0ad643f5a0:en', $request->getActiveKey());
        $this->assertSame([], $request->getChecks());
        $this->assertSame([], $request->getApprovals());
    }

    public function testApprovalBelowThresholdKeepsRequestPending(): void
    {
        $request = $this->createRequest(2);

        $request->addApproval($this->prophesize(UserInterface::class)->reveal(), 'Looks good');

        $this->assertSame(WorkflowTransitionRequestStatusEnum::PENDING, $request->getStatus());
        $this->assertCount(1, $request->getApprovals());
        $this->assertSame('Looks good', $request->getApprovals()[0]->getComment());
    }

    public function testRejectionsDoNotBlockTheRequiredApprovals(): void
    {
        $request = $this->createRequest(3);

        $request->addApproval($this->prophesize(UserInterface::class)->reveal());
        $request->addApproval($this->prophesize(UserInterface::class)->reveal());
        $request->addApproval($this->prophesize(UserInterface::class)->reveal());
        $request->addRejection($this->prophesize(UserInterface::class)->reveal(), 'Typo in the headline');
        $request->addRejection($this->prophesize(UserInterface::class)->reveal(), 'Wrong image');

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::APPROVED,
            $request->getStatus(),
            'A rejection is a comment that does not count, it never vetoes the approvals.',
        );
    }

    public function testValidatorApprovalDoesNotCountTowardsTheThreshold(): void
    {
        $request = $this->createRequest(2);
        $request->addCheck('unpublished_references');

        $request->addApproval($this->prophesize(UserInterface::class)->reveal());
        $request->getCheck('unpublished_references')?->pass();

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::PENDING,
            $request->getStatus(),
            'A passed check reports on the content, it does not stand in for the second person.',
        );
        $this->assertSame(1, $request->countHumanApprovals());

        $request->addApproval($this->prophesize(UserInterface::class)->reveal());

        $this->assertSame(WorkflowTransitionRequestStatusEnum::APPROVED, $request->getStatus());
    }

    public function testValidatorRejectionLeavesTheHumansInCharge(): void
    {
        $request = $this->createRequest(1);
        $request->addCheck('unpublished_references');
        $request->getCheck('unpublished_references')?->fail('2 selected examples are not published: 1, 2');

        $this->assertSame(WorkflowTransitionRequestStatusEnum::PENDING, $request->getStatus());

        $request->addApproval($this->prophesize(UserInterface::class)->reveal());

        $this->assertSame(WorkflowTransitionRequestStatusEnum::APPROVED, $request->getStatus());
    }

    public function testSameUserSwitchingToRejectWithdrawsTheApproval(): void
    {
        $user = $this->prophesize(UserInterface::class)->reveal();
        $request = $this->createRequest(1);

        $request->addApproval($user, 'Fine by me');
        $this->assertSame(WorkflowTransitionRequestStatusEnum::APPROVED, $request->getStatus());

        $request->addRejection($user, 'Changed my mind');

        $this->assertCount(1, $request->getApprovals());
        $this->assertSame(WorkflowTransitionRequestApprovalStatusEnum::REJECTED, $request->getApprovals()[0]->getStatus());
        $this->assertSame('Changed my mind', $request->getApprovals()[0]->getComment());
        $this->assertSame(WorkflowTransitionRequestStatusEnum::PENDING, $request->getStatus());
    }

    public function testZeroRequiredApprovalsApprovesWithoutReviewer(): void
    {
        $request = $this->createRequest(0);

        $this->assertSame(WorkflowTransitionRequestStatusEnum::APPROVED, $request->getStatus());
    }

    public function testClosedRequestThrowsOnApproval(): void
    {
        $request = $this->createRequest();
        $request->cancel();

        $this->expectException(WorkflowTransitionRequestClosedException::class);
        $request->addApproval($this->prophesize(UserInterface::class)->reveal());
    }

    public function testCreatorCannotApproveOwnRequest(): void
    {
        $creator = $this->prophesize(UserInterface::class)->reveal();
        $request = $this->createRequest();
        $request->setCreator($creator);

        $this->expectException(SelfReviewNotAllowedException::class);
        $request->addApproval($creator);
    }

    public function testApprovalWithDeletedCreatorIsAllowed(): void
    {
        $request = new WorkflowTransitionRequest('pages', '4d3e0d90-4cc8-46c4-a6dc-9f0ad643f5a0', 'en', 'default', 1);

        $request->addApproval($this->prophesize(UserInterface::class)->reveal());

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::APPROVED,
            $request->getStatus(),
            'A deleted creator nulls the column, which must not block every review of the request.',
        );
    }

    public function testCancelClosesTheRequest(): void
    {
        $request = $this->createRequest();
        $request->setCreator($this->prophesize(UserInterface::class)->reveal());
        $request->addApproval($this->prophesize(UserInterface::class)->reveal());

        $request->cancel();

        $this->assertSame(WorkflowTransitionRequestStatusEnum::CANCELLED, $request->getStatus());
        $this->assertNull($request->getActiveKey(), 'A cancelled request must not block the next one.');
        $this->assertFalse($request->isOpen());
    }

    public function testCancelOnAlreadyCancelledRequestIsNoOp(): void
    {
        $request = $this->createRequest();
        $request->cancel();
        $request->cancel();

        $this->assertSame(WorkflowTransitionRequestStatusEnum::CANCELLED, $request->getStatus());
    }

    public function testPublishTransitionsApprovedRequestToPublished(): void
    {
        $request = $this->createRequest(1);
        $request->addApproval($this->prophesize(UserInterface::class)->reveal());

        $request->publish();

        $this->assertSame(WorkflowTransitionRequestStatusEnum::PUBLISHED, $request->getStatus());
        $this->assertNull($request->getActiveKey());
    }

    public function testABlockingCheckHoldsARequestThatHasEveryApproval(): void
    {
        $request = $this->createRequest(1);
        $request->addCheck('llm_review', blocking: true);
        $request->addApproval($this->prophesize(UserInterface::class)->reveal());

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::PENDING,
            $request->getStatus(),
            'A blocking check that has not answered yet still holds the request.',
        );

        $request->getCheck('llm_review')?->fail('The intro contradicts the headline.');

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::PENDING,
            $request->getStatus(),
            'A failed blocking check holds it too, however many people approved.',
        );

        $request->getCheck('llm_review')?->pass();

        $this->assertSame(WorkflowTransitionRequestStatusEnum::APPROVED, $request->getStatus());
    }

    public function testANonBlockingCheckNeverHoldsARequest(): void
    {
        $request = $this->createRequest(1);
        $request->addCheck('llm_review');
        $request->addApproval($this->prophesize(UserInterface::class)->reveal());

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::APPROVED,
            $request->getStatus(),
            'A pending informational check is a report, not a gate.',
        );

        $request->getCheck('llm_review')?->fail('Reads oddly.');

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::APPROVED,
            $request->getStatus(),
            'Neither is a failed one.',
        );
    }

    public function testABlockingCheckCannotSatisfyAMissingApproval(): void
    {
        $request = $this->createRequest(1);
        $request->addCheck('llm_review', blocking: true);
        $request->getCheck('llm_review')?->pass();

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::PENDING,
            $request->getStatus(),
            'A check reports on the content, it never stands in for a reviewer.',
        );
        $this->assertSame(0, $request->countHumanApprovals());
    }

    private function createRequest(int $requiredHumanApprovalCount = 1): WorkflowTransitionRequest
    {
        $request = new WorkflowTransitionRequest(
            resourceKey: 'pages',
            resourceId: '4d3e0d90-4cc8-46c4-a6dc-9f0ad643f5a0',
            locale: 'en',
            workflowName: WorkflowTransitionRequest::DEFAULT_WORKFLOW_NAME,
            requiredHumanApprovalCount: $requiredHumanApprovalCount,
        );

        // A creator distinct from every reviewer stub, so the self-review guard never fires by accident.
        $request->setCreator($this->prophesize(UserInterface::class)->reveal());

        return $request;
    }
}
