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

namespace Sulu\Content\Domain\Model\WorkflowTransitionRequest;

use Symfony\Component\Uid\Uuid;

/**
 * One automated check on a request. It reports on the content, it never stands in for a reviewer, so
 * it can never satisfy a required approval. Whether a failure stops the publish is decided per
 * workflow and snapshotted here, so a config change cannot move the bar under an open request.
 */
class WorkflowTransitionRequestCheck
{
    private string $id;

    private WorkflowTransitionRequest $workflowTransitionRequest;

    private string $validatorKey;

    private bool $blocking;

    private WorkflowTransitionRequestCheckStatusEnum $status = WorkflowTransitionRequestCheckStatusEnum::PENDING;

    private ?string $comment = null;

    private ?\DateTimeImmutable $decidedAt = null;

    public function __construct(
        WorkflowTransitionRequest $workflowTransitionRequest,
        string $validatorKey,
        bool $blocking = false,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->workflowTransitionRequest = $workflowTransitionRequest;
        $this->validatorKey = $validatorKey;
        $this->blocking = $blocking;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getValidatorKey(): string
    {
        return $this->validatorKey;
    }

    public function isBlocking(): bool
    {
        return $this->blocking;
    }

    public function getStatus(): WorkflowTransitionRequestCheckStatusEnum
    {
        return $this->status;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getDecidedAt(): ?\DateTimeImmutable
    {
        return $this->decidedAt;
    }

    /**
     * A blocking check holds the request until it has passed. Pending is not a failure, but it is
     * not a pass either, so a blocking check that has not answered yet still holds.
     */
    public function blocksApproval(): bool
    {
        return $this->blocking && WorkflowTransitionRequestCheckStatusEnum::PASSED !== $this->status;
    }

    public function pass(?string $comment = null): void
    {
        $this->settle(WorkflowTransitionRequestCheckStatusEnum::PASSED, $comment, new \DateTimeImmutable());
    }

    public function fail(string $comment): void
    {
        $this->settle(WorkflowTransitionRequestCheckStatusEnum::FAILED, $comment, new \DateTimeImmutable());
    }

    public function resetToPending(): void
    {
        $this->status = WorkflowTransitionRequestCheckStatusEnum::PENDING;
        $this->comment = null;
        $this->decidedAt = null;
    }

    /**
     * Mirrors a settlement that already happened in the database onto the hydrated row, so an inline
     * run answers the same as a worker run.
     *
     * @internal
     */
    public function settle(WorkflowTransitionRequestCheckStatusEnum $status, ?string $comment, \DateTimeImmutable $decidedAt): void
    {
        $this->status = $status;
        $this->comment = $comment;
        $this->decidedAt = $decidedAt;
    }
}
