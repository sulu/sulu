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
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Domain\Exception\PublicationRequestClosedException;
use Sulu\Content\Domain\Model\PublicationRequest\PublicationRequest;
use Sulu\Content\Domain\Model\PublicationRequest\PublicationRequestReviewerStatusEnum;
use Sulu\Content\Domain\Model\PublicationRequest\PublicationRequestStatusEnum;

#[CoversClass(PublicationRequest::class)]
#[CoversClass(PublicationRequestStatusEnum::class)]
class PublicationRequestTest extends TestCase
{
    public function testConstructInitializesOpenRequest(): void
    {
        $request = $this->createRequest();

        $this->assertNotSame('', $request->getId());
        $this->assertSame(PublicationRequestStatusEnum::PENDING, $request->getStatus());
        $this->assertSame('pages:4d3e0d90-4cc8-46c4-a6dc-9f0ad643f5a0:en', $request->getActiveKey());
        $this->assertEqualsWithDelta(new \DateTimeImmutable(), $request->getRequestedAt(), 5);
    }

    public function testAddApprovalTransitionsToApproved(): void
    {
        $user = $this->createStub(UserInterface::class);
        $request = $this->createRequest();

        $request->addApproval($user, 'Looks good');

        $this->assertSame(PublicationRequestStatusEnum::APPROVED, $request->getStatus());
        $this->assertSame('pages:4d3e0d90-4cc8-46c4-a6dc-9f0ad643f5a0:en', $request->getActiveKey());
        $this->assertCount(1, $request->getReviewers());
        $this->assertSame('Looks good', $request->getReviewers()[0]->getComment());
    }

    public function testAddRejectionTransitionsToRejected(): void
    {
        $user = $this->createStub(UserInterface::class);
        $request = $this->createRequest();

        $request->addRejection($user, 'Needs changes');

        $this->assertSame(PublicationRequestStatusEnum::REJECTED, $request->getStatus());
        $this->assertNull($request->getActiveKey());
        $this->assertCount(1, $request->getReviewers());
        $this->assertSame('Needs changes', $request->getReviewers()[0]->getComment());
    }

    public function testSameUserChangingMindUpdatesExistingReviewer(): void
    {
        $user = $this->createStub(UserInterface::class);
        $request = $this->createRequest();

        $request->addRejection($user, 'Needs changes');
        $request->addApproval($user, 'Changed mind');

        $this->assertSame(PublicationRequestStatusEnum::APPROVED, $request->getStatus());
        $this->assertCount(1, $request->getReviewers());
        $this->assertSame(PublicationRequestReviewerStatusEnum::APPROVED, $request->getReviewers()[0]->getStatus());
        $this->assertSame('Changed mind', $request->getReviewers()[0]->getComment());
    }

    public function testDifferentUsersCreateSeparateReviewers(): void
    {
        $userA = $this->createStub(UserInterface::class);
        $userB = $this->createStub(UserInterface::class);
        $request = $this->createRequest();

        $request->addApproval($userA);
        $request->addApproval($userB);

        $this->assertCount(2, $request->getReviewers());
        $this->assertSame(PublicationRequestStatusEnum::APPROVED, $request->getStatus());
    }

    public function testRejectionByOneUserOverridesOtherApprovalsInStatus(): void
    {
        $userA = $this->createStub(UserInterface::class);
        $userB = $this->createStub(UserInterface::class);
        $request = $this->createRequest();

        $request->addApproval($userA);
        $request->addRejection($userB, 'Needs changes');

        $this->assertSame(PublicationRequestStatusEnum::REJECTED, $request->getStatus());
        $this->assertNull($request->getActiveKey());
    }

    public function testClosedRequestThrowsOnApproval(): void
    {
        $user = $this->createStub(UserInterface::class);
        $request = $this->createRequest();
        $request->cancel();

        $this->expectException(PublicationRequestClosedException::class);
        $request->addApproval($user);
    }

    public function testClosedRequestThrowsOnRejection(): void
    {
        $user = $this->createStub(UserInterface::class);
        $request = $this->createRequest();
        $request->cancel();

        $this->expectException(PublicationRequestClosedException::class);
        $request->addRejection($user);
    }

    public function testCancelTransitionsToTerminalStateAndClearsActiveKey(): void
    {
        $request = $this->createRequest();
        $request->cancel();

        $this->assertSame(PublicationRequestStatusEnum::CANCELLED, $request->getStatus());
        $this->assertNull($request->getActiveKey());
    }

    public function testCancelOnAlreadyCancelledRequestIsNoOp(): void
    {
        $request = $this->createRequest();
        $request->cancel();
        $request->cancel();

        $this->assertSame(PublicationRequestStatusEnum::CANCELLED, $request->getStatus());
    }

    public function testMarkPublishedTransitionsApprovedRequestToPublished(): void
    {
        $user = $this->createStub(UserInterface::class);
        $request = $this->createRequest();
        $request->addApproval($user);

        $request->publish();

        $this->assertSame(PublicationRequestStatusEnum::PUBLISHED, $request->getStatus());
        $this->assertNull($request->getActiveKey());
    }

    private function createRequest(): PublicationRequest
    {
        return new PublicationRequest(
            resourceKey: 'pages',
            resourceId: '4d3e0d90-4cc8-46c4-a6dc-9f0ad643f5a0',
            locale: 'en',
        );
    }
}
