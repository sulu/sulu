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
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\DecisionMessage;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecision;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionStatusEnum;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestPlaceEnum;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;

#[CoversClass(WorkflowTransitionRequest::class)]
#[CoversClass(WorkflowTransitionRequestDecision::class)]
#[CoversClass(WorkflowTransitionRequestPlaceEnum::class)]
#[CoversClass(WorkflowTransitionRequestStatusEnum::class)]
class WorkflowTransitionRequestTest extends TestCase
{
    use ProphecyTrait;

    private static int $userId = 0;

    public function testConstructInitializesOpenRequest(): void
    {
        $request = $this->createRequest();

        $this->assertNotSame('', $request->getId());
        $this->assertSame(RequestWorkflow::DEFAULT_NAME, $request->getWorkflowName());
        $this->assertSame(WorkflowTransitionRequestStatusEnum::PENDING, $request->getStatus(1));
        $this->assertSame('pages:4d3e0d90-4cc8-46c4-a6dc-9f0ad643f5a0:en', $request->getActiveKey());
        $this->assertSame([], $request->getDecisions());
    }

    public function testApprovalBelowThresholdKeepsRequestPending(): void
    {
        $request = $this->createRequest();

        $request->addApproval($this->createUser(), DecisionMessage::text('Looks good'));

        $this->assertSame(WorkflowTransitionRequestStatusEnum::PENDING, $request->getStatus(2));
        $this->assertCount(1, $request->getDecisions());
        $this->assertSame('Looks good', $request->getDecisions()[0]->getMessages()[0]->text);
    }

    public function testRejectionsDoNotBlockTheRequiredApprovals(): void
    {
        $request = $this->createRequest();

        $request->addApproval($this->createUser());
        $request->addApproval($this->createUser());
        $request->addApproval($this->createUser());
        $request->addRejection($this->createUser(), DecisionMessage::text('Typo in the headline'));
        $request->addRejection($this->createUser(), DecisionMessage::text('Wrong image'));

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::APPROVED,
            $request->getStatus(3),
            'A rejection is a comment that does not count, it never vetoes the approvals.',
        );
    }

    public function testValidatorApprovalDoesNotCountTowardsTheThreshold(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('unpublished_references');

        $request->addApproval($this->createUser());
        $this->settle($request, 'unpublished_references', WorkflowTransitionRequestDecisionStatusEnum::APPROVED);

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::PENDING,
            $request->getStatus(2),
            'A passed check reports on the content, it does not stand in for the second person.',
        );
        $this->assertSame(1, $request->countUserApprovals());

        $request->addApproval($this->createUser());

        $this->assertSame(WorkflowTransitionRequestStatusEnum::APPROVED, $request->getStatus(2));
    }

    public function testValidatorRejectionLeavesTheHumansInCharge(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('unpublished_references');
        $this->settle($request, 'unpublished_references', WorkflowTransitionRequestDecisionStatusEnum::REJECTED, '2 selected examples are not published: 1, 2');

        $this->assertSame(WorkflowTransitionRequestStatusEnum::PENDING, $request->getStatus(1));

        $request->addApproval($this->createUser());

        $this->assertSame(WorkflowTransitionRequestStatusEnum::APPROVED, $request->getStatus(1));
    }

    public function testSameUserSwitchingToRejectWithdrawsTheApproval(): void
    {
        $user = $this->createUser();
        $request = $this->createRequest();

        $request->addApproval($user, DecisionMessage::text('Fine by me'));
        $this->assertSame(WorkflowTransitionRequestStatusEnum::APPROVED, $request->getStatus(1));

        $request->addRejection($user, DecisionMessage::text('Changed my mind'));

        $this->assertCount(1, $request->getDecisions());
        $this->assertSame(WorkflowTransitionRequestDecisionStatusEnum::REJECTED, $request->getDecisions()[0]->getStatus());
        $this->assertSame('Changed my mind', $request->getDecisions()[0]->getMessages()[0]->text);
        $this->assertSame(WorkflowTransitionRequestStatusEnum::PENDING, $request->getStatus(1));
    }

    public function testZeroRequiredApprovalsApprovesWithoutReviewer(): void
    {
        $request = $this->createRequest();

        $this->assertSame(WorkflowTransitionRequestStatusEnum::APPROVED, $request->getStatus(0));
    }

    public function testClosedRequestThrowsOnApproval(): void
    {
        $request = $this->createRequest();
        $request->cancel();

        $this->expectException(WorkflowTransitionRequestClosedException::class);
        $request->addApproval($this->createUser());
    }

    public function testCreatorCannotApproveOwnRequest(): void
    {
        $creator = $this->createUser();
        $request = $this->createRequest();
        $request->setCreator($creator);

        $this->expectException(SelfReviewNotAllowedException::class);
        $request->addApproval($creator);
    }

    public function testApprovalWithDeletedCreatorIsAllowed(): void
    {
        $request = new WorkflowTransitionRequest('pages', '4d3e0d90-4cc8-46c4-a6dc-9f0ad643f5a0', 'en', 'default');

        $request->addApproval($this->createUser());

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::APPROVED,
            $request->getStatus(1),
            'A deleted creator nulls the column, which must not block every review of the request.',
        );
    }

    public function testCancelClosesTheRequest(): void
    {
        $request = $this->createRequest();
        $request->setCreator($this->createUser());
        $request->addApproval($this->createUser());

        $request->cancel();

        $this->assertSame(WorkflowTransitionRequestStatusEnum::CANCELLED, $request->getStatus(1));
        $this->assertNull($request->getActiveKey(), 'A cancelled request must not block the next one.');
        $this->assertFalse($request->isOpen());
    }

    public function testCancelOnAlreadyCancelledRequestIsNoOp(): void
    {
        $request = $this->createRequest();
        $request->cancel();
        $request->cancel();

        $this->assertSame(WorkflowTransitionRequestStatusEnum::CANCELLED, $request->getStatus(1));
    }

    public function testPublishTransitionsApprovedRequestToPublished(): void
    {
        $request = $this->createRequest();
        $request->addApproval($this->createUser());

        $request->publish();

        $this->assertSame(WorkflowTransitionRequestStatusEnum::PUBLISHED, $request->getStatus(1));
        $this->assertNull($request->getActiveKey());
    }

    public function testABlockingCheckHoldsARequestThatHasEveryApproval(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('llm_review');
        $request->addApproval($this->createUser());

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::PENDING,
            $request->getStatus(1, ['llm_review']),
            'A required check that has not answered yet still holds the request.',
        );

        $this->settle($request, 'llm_review', WorkflowTransitionRequestDecisionStatusEnum::REJECTED, 'The intro contradicts the headline.');

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::PENDING,
            $request->getStatus(1, ['llm_review']),
            'A failed required check holds it too, however many people approved.',
        );

        $this->settle($request, 'llm_review', WorkflowTransitionRequestDecisionStatusEnum::APPROVED);

        $this->assertSame(WorkflowTransitionRequestStatusEnum::APPROVED, $request->getStatus(1, ['llm_review']));
    }

    public function testANonBlockingCheckNeverHoldsARequest(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('llm_review');
        $request->addApproval($this->createUser());

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::APPROVED,
            $request->getStatus(1),
            'A pending informational check is a report, not a gate.',
        );

        $this->settle($request, 'llm_review', WorkflowTransitionRequestDecisionStatusEnum::REJECTED, 'Reads oddly.');

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::APPROVED,
            $request->getStatus(1),
            'Neither is a failed one.',
        );
    }

    public function testABlockingCheckCannotSatisfyAMissingApproval(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('llm_review');
        $this->settle($request, 'llm_review', WorkflowTransitionRequestDecisionStatusEnum::APPROVED);

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::PENDING,
            $request->getStatus(1, ['llm_review']),
            'A check reports on the content, it never stands in for a reviewer.',
        );
        $this->assertSame(0, $request->countUserApprovals());
    }

    /**
     * Distinct ids, because a decision is keyed by its subject.
     */
    private function createUser(): UserInterface
    {
        $user = $this->prophesize(UserInterface::class);
        $user->getId()->willReturn(++self::$userId);

        return $user->reveal();
    }

    private function settle(
        WorkflowTransitionRequest $request,
        string $validatorKey,
        WorkflowTransitionRequestDecisionStatusEnum $status,
        ?string $text = null,
    ): void {
        $request->getValidatorDecision($validatorKey)?->settle(
            $status,
            null === $text ? [] : [DecisionMessage::text($text)],
            new \DateTimeImmutable(),
        );
    }

    private function createRequest(): WorkflowTransitionRequest
    {
        $request = new WorkflowTransitionRequest(
            resourceKey: 'pages',
            resourceId: '4d3e0d90-4cc8-46c4-a6dc-9f0ad643f5a0',
            locale: 'en',
            workflowName: WorkflowTransitionRequest::DEFAULT_WORKFLOW_NAME,
        );

        // A creator distinct from every reviewer stub, so the self-review guard never fires by accident.
        $request->setCreator($this->createUser());

        return $request;
    }
}
