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
use Sulu\Content\Application\RequestWorkflow\WorkflowTransitionRequestStatusResolverInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Action\WorkflowTransitionRequestActionInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Action\WorkflowTransitionRequestActionRegistryInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\WorkflowTransitionRequestReviewBypassChecker;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;

#[CoversClass(WorkflowTransitionRequestReviewBypassChecker::class)]
class WorkflowTransitionRequestReviewBypassCheckerTest extends TestCase
{
    use ProphecyTrait;

    public function testAPendingRequestIsBypassed(): void
    {
        $workflowTransitionRequestReviewBypassChecker = $this->createChecker(WorkflowTransitionRequestStatusEnum::PENDING);

        $this->assertTrue($workflowTransitionRequestReviewBypassChecker->isBypassingReview($this->createRequest()));
    }

    public function testARequestWhoseWorkflowIsUnknownIsBypassed(): void
    {
        $workflowTransitionRequestReviewBypassChecker = $this->createChecker(WorkflowTransitionRequestStatusEnum::UNKNOWN);

        $this->assertTrue($workflowTransitionRequestReviewBypassChecker->isBypassingReview($this->createRequest()));
    }

    public function testAnApprovedRequestIsNotBypassed(): void
    {
        $workflowTransitionRequestReviewBypassChecker = $this->createChecker(WorkflowTransitionRequestStatusEnum::APPROVED);

        $this->assertFalse($workflowTransitionRequestReviewBypassChecker->isBypassingReview($this->createRequest()));
    }

    /**
     * An action that replaces the publish, such as a scheduled one, carries out what the request asked
     * for, even while it still waits for approvals.
     */
    public function testAPendingRequestWhoseActionReplacesThePublishIsNotBypassed(): void
    {
        $workflowTransitionRequestReviewBypassChecker = $this->createChecker(WorkflowTransitionRequestStatusEnum::PENDING, replacesPublish: true);

        $this->assertFalse($workflowTransitionRequestReviewBypassChecker->isBypassingReview($this->createRequest()));
    }

    private function createChecker(
        WorkflowTransitionRequestStatusEnum $status,
        bool $replacesPublish = false,
    ): WorkflowTransitionRequestReviewBypassChecker {
        $workflowTransitionRequestStatusResolver = $this->prophesize(WorkflowTransitionRequestStatusResolverInterface::class);
        $workflowTransitionRequestStatusResolver->resolve(Argument::any())->willReturn($status);

        $workflowTransitionRequestAction = $this->prophesize(WorkflowTransitionRequestActionInterface::class);
        $workflowTransitionRequestAction->replacesPublish(Argument::any())->willReturn($replacesPublish);

        $workflowTransitionRequestActionRegistry = $this->prophesize(WorkflowTransitionRequestActionRegistryInterface::class);
        $workflowTransitionRequestActionRegistry->all()->willReturn([$workflowTransitionRequestAction->reveal()]);

        return new WorkflowTransitionRequestReviewBypassChecker(
            $workflowTransitionRequestStatusResolver->reveal(),
            $workflowTransitionRequestActionRegistry->reveal(),
        );
    }

    private function createRequest(): WorkflowTransitionRequest
    {
        return new WorkflowTransitionRequest(Example::RESOURCE_KEY, '1', 'en', 'default');
    }
}
