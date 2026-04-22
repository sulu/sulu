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
use Sulu\Content\Domain\Model\PublicationRequest\PublicationRequestReviewer;
use Sulu\Content\Domain\Model\PublicationRequest\PublicationRequestReviewerStatusEnum;

#[CoversClass(PublicationRequestReviewer::class)]
#[CoversClass(PublicationRequestReviewerStatusEnum::class)]
class PublicationRequestReviewerTest extends TestCase
{
    public function testConstructCreatesApprovedReviewer(): void
    {
        $user = $this->createStub(UserInterface::class);
        $reviewer = new PublicationRequestReviewer($user, PublicationRequestReviewerStatusEnum::APPROVED, 'Looks good');

        $this->assertNotSame('', $reviewer->getId());
        $this->assertSame(PublicationRequestReviewerStatusEnum::APPROVED, $reviewer->getStatus());
        $this->assertSame('Looks good', $reviewer->getComment());
        $this->assertSame($user, $reviewer->getCreator());
    }

    public function testConstructCreatesRejectedReviewer(): void
    {
        $user = $this->createStub(UserInterface::class);
        $reviewer = new PublicationRequestReviewer($user, PublicationRequestReviewerStatusEnum::REJECTED, 'Needs changes');

        $this->assertSame(PublicationRequestReviewerStatusEnum::REJECTED, $reviewer->getStatus());
        $this->assertSame('Needs changes', $reviewer->getComment());
    }

    public function testConstructWithNullCommentDefaultsToNull(): void
    {
        $user = $this->createStub(UserInterface::class);
        $reviewer = new PublicationRequestReviewer($user, PublicationRequestReviewerStatusEnum::APPROVED);

        $this->assertSame(PublicationRequestReviewerStatusEnum::APPROVED, $reviewer->getStatus());
        $this->assertNull($reviewer->getComment());
    }

    public function testUpdateChangesStatusAndComment(): void
    {
        $user = $this->createStub(UserInterface::class);
        $reviewer = new PublicationRequestReviewer($user, PublicationRequestReviewerStatusEnum::REJECTED, 'Needs changes');

        $reviewer->update(PublicationRequestReviewerStatusEnum::APPROVED, 'Changed mind');

        $this->assertSame(PublicationRequestReviewerStatusEnum::APPROVED, $reviewer->getStatus());
        $this->assertSame('Changed mind', $reviewer->getComment());
    }

    public function testUpdateClearsCommentWhenNull(): void
    {
        $user = $this->createStub(UserInterface::class);
        $reviewer = new PublicationRequestReviewer($user, PublicationRequestReviewerStatusEnum::REJECTED, 'Needs changes');

        $reviewer->update(PublicationRequestReviewerStatusEnum::APPROVED);

        $this->assertNull($reviewer->getComment());
    }
}
