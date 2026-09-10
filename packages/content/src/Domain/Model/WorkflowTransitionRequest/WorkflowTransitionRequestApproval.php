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

use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\Uid\Uuid;

class WorkflowTransitionRequestApproval
{
    private string $id;

    private WorkflowTransitionRequest $workflowTransitionRequest;

    private ?UserInterface $user = null;

    private WorkflowTransitionRequestApprovalStatusEnum $status;

    private ?string $comment = null;

    private \DateTimeImmutable $decidedAt;

    public function __construct(
        WorkflowTransitionRequest $workflowTransitionRequest,
        UserInterface $user,
        WorkflowTransitionRequestApprovalStatusEnum $status,
        ?string $comment = null,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->workflowTransitionRequest = $workflowTransitionRequest;
        $this->user = $user;
        $this->status = $status;
        $this->comment = $comment;
        $this->decidedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function getStatus(): WorkflowTransitionRequestApprovalStatusEnum
    {
        return $this->status;
    }

    public function isApproved(): bool
    {
        return WorkflowTransitionRequestApprovalStatusEnum::APPROVED === $this->status;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getDecidedAt(): \DateTimeImmutable
    {
        return $this->decidedAt;
    }

    public function decide(WorkflowTransitionRequestApprovalStatusEnum $status, ?string $comment = null): void
    {
        $this->status = $status;
        $this->comment = $comment;
        $this->decidedAt = new \DateTimeImmutable();
    }
}
