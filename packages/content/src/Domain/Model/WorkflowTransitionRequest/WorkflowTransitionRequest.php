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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Domain\Exception\SelfReviewNotAllowedException;
use Sulu\Content\Domain\Exception\WorkflowTransitionRequestClosedException;
use Symfony\Component\Uid\Uuid;

class WorkflowTransitionRequest implements AuditableInterface
{
    use AuditableTrait;

    public const DEFAULT_WORKFLOW_NAME = 'default';

    private string $id;

    private WorkflowTransitionRequestLifecycleEnum $lifecycle = WorkflowTransitionRequestLifecycleEnum::OPEN;

    private ?string $activeKey = null;

    private string $workflowName;

    /**
     * @var Collection<int, WorkflowTransitionRequestApproval>
     */
    private Collection $approvals;

    /**
     * @var Collection<int, WorkflowTransitionRequestCheck>
     */
    private Collection $checks;

    public function __construct(
        private readonly string $resourceKey,
        private readonly string $resourceId,
        private readonly string $locale,
        string $workflowName,
        private readonly int $requiredHumanApprovalCount,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->approvals = new ArrayCollection();
        $this->checks = new ArrayCollection();
        $this->workflowName = $workflowName;
        $this->created = new \DateTimeImmutable();
        $this->syncActiveKey();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function isOpen(): bool
    {
        return $this->lifecycle->isOpen();
    }

    /**
     * Derived on read and never stored, so two handlers settling concurrently cannot overwrite
     * each other's verdict.
     */
    public function getStatus(): WorkflowTransitionRequestStatusEnum
    {
        if (WorkflowTransitionRequestLifecycleEnum::CANCELLED === $this->lifecycle) {
            return WorkflowTransitionRequestStatusEnum::CANCELLED;
        }

        if (WorkflowTransitionRequestLifecycleEnum::PUBLISHED === $this->lifecycle) {
            return WorkflowTransitionRequestStatusEnum::PUBLISHED;
        }

        if ($this->countHumanApprovals() < $this->requiredHumanApprovalCount) {
            return WorkflowTransitionRequestStatusEnum::PENDING;
        }

        // People carry the request, checks can hold it back: a check configured to block keeps the
        // request pending until it passed, however many approvals it collected.
        foreach ($this->checks as $check) {
            if ($check->blocksApproval()) {
                return WorkflowTransitionRequestStatusEnum::PENDING;
            }
        }

        return WorkflowTransitionRequestStatusEnum::APPROVED;
    }

    public function countHumanApprovals(): int
    {
        $approvals = 0;
        foreach ($this->approvals as $approval) {
            // Deleting a user nulls the row's user, leaving it owned by nobody. It stays as history
            // but must not keep voting.
            if ($approval->isApproved() && null !== $approval->getUser()) {
                ++$approvals;
            }
        }

        return $approvals;
    }

    public function countHumanRejections(): int
    {
        $rejections = 0;
        foreach ($this->approvals as $approval) {
            if (!$approval->isApproved()) {
                ++$rejections;
            }
        }

        return $rejections;
    }

    public function getActiveKey(): ?string
    {
        return $this->activeKey;
    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function getRequiredHumanApprovalCount(): int
    {
        return $this->requiredHumanApprovalCount;
    }

    /**
     * @return list<WorkflowTransitionRequestApproval>
     */
    public function getApprovals(): array
    {
        return \array_values($this->approvals->toArray());
    }

    /**
     * @return list<WorkflowTransitionRequestCheck>
     */
    public function getChecks(): array
    {
        return \array_values($this->checks->toArray());
    }

    public function addCheck(string $validatorKey, bool $blocking = false): void
    {
        if (null !== $this->getCheck($validatorKey)) {
            return;
        }

        $this->checks->add(new WorkflowTransitionRequestCheck($this, $validatorKey, $blocking));
    }

    public function getCheck(string $validatorKey): ?WorkflowTransitionRequestCheck
    {
        foreach ($this->checks as $check) {
            if ($validatorKey === $check->getValidatorKey()) {
                return $check;
            }
        }

        return null;
    }

    public function addApproval(UserInterface $user, ?string $comment = null): void
    {
        $this->decide($user, WorkflowTransitionRequestApprovalStatusEnum::APPROVED, $comment);
    }

    public function addRejection(UserInterface $user, string $comment): void
    {
        $this->decide($user, WorkflowTransitionRequestApprovalStatusEnum::REJECTED, $comment);
    }

    public function getApprovalOf(UserInterface $user): ?WorkflowTransitionRequestApproval
    {
        foreach ($this->approvals as $approval) {
            if ($approval->getUser() === $user) {
                return $approval;
            }
        }

        return null;
    }

    public function cancel(): void
    {
        $this->transitionTo(WorkflowTransitionRequestLifecycleEnum::CANCELLED);
    }

    public function publish(): void
    {
        $this->transitionTo(WorkflowTransitionRequestLifecycleEnum::PUBLISHED);
    }

    private function decide(
        UserInterface $user,
        WorkflowTransitionRequestApprovalStatusEnum $status,
        ?string $comment,
    ): void {
        if (!$this->lifecycle->isOpen()) {
            throw new WorkflowTransitionRequestClosedException($this);
        }

        if ($this->getCreator() === $user) {
            throw new SelfReviewNotAllowedException($this);
        }

        $approval = $this->getApprovalOf($user);
        if (null !== $approval) {
            $approval->decide($status, $comment);

            return;
        }

        $this->approvals->add(new WorkflowTransitionRequestApproval($this, $user, $status, $comment));
    }

    private function transitionTo(WorkflowTransitionRequestLifecycleEnum $toLifecycle): void
    {
        if (!$this->lifecycle->isOpen()) {
            return;
        }

        $this->lifecycle = $toLifecycle;
        $this->syncActiveKey();
    }

    private function syncActiveKey(): void
    {
        $this->activeKey = $this->lifecycle->isOpen()
            ? \sprintf('%s:%s:%s', $this->resourceKey, $this->resourceId, $this->locale)
            : null;
    }
}
