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
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionStatusEnum;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestPlaceEnum;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;
use Symfony\Component\Uid\Uuid;

/**
 * @internal except the getters marked `@experimental`, which an action of a bundle may call, see
 *           {@see \Sulu\Content\Application\WorkflowTransitionRequest\Action\WorkflowTransitionRequestActionInterface}
 */
class WorkflowTransitionRequest implements AuditableInterface
{
    use AuditableTrait;

    public const DEFAULT_WORKFLOW_NAME = 'default';

    private string $id;

    private WorkflowTransitionRequestPlaceEnum $place = WorkflowTransitionRequestPlaceEnum::OPEN;

    private ?string $activeKey = null;

    private string $workflowName;

    /**
     * What the requester filled into the request form. The core stores it as it comes and reads
     * nothing from it, so a bundle that extends the form owns its keys.
     *
     * @var array<string, mixed>|null
     */
    private ?array $data = null;

    /**
     * @var Collection<int, WorkflowTransitionRequestDecision>
     */
    private Collection $decisions;

    public function __construct(
        private readonly string $resourceKey,
        private readonly string $resourceId,
        private readonly string $locale,
        string $workflowName,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->decisions = new ArrayCollection();
        $this->workflowName = $workflowName;
        $this->created = new \DateTimeImmutable();
        $this->syncActiveKey();
    }

    /**
     * @experimental This is an experimental feature and may change in future releases.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @experimental This is an experimental feature and may change in future releases.
     */
    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    /**
     * @experimental This is an experimental feature and may change in future releases.
     */
    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    /**
     * @experimental This is an experimental feature and may change in future releases.
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @experimental This is an experimental feature and may change in future releases.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data ?? [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = [] === $data ? null : $data;
    }

    /**
     * @experimental This is an experimental feature and may change in future releases.
     */
    public function isOpen(): bool
    {
        return $this->place->isOpen();
    }

    public function getPlace(): WorkflowTransitionRequestPlaceEnum
    {
        return $this->place;
    }

    /**
     * Derived on read and never stored, so two handlers settling concurrently cannot overwrite
     * each other's verdict. Both thresholds come from the workflow config rather than from the row,
     * so raising or lowering them applies to open requests as well.
     *
     * The set of validators does not: the rows are written when the request is created, so a
     * validator added to the config afterwards is not checked for requests that are already open.
     * Reading it live instead would leave them waiting on a row nothing can ever settle.
     *
     * @param list<string> $requiredValidatorKeys
     */
    public function getStatus(int $requiredUserApprovals, array $requiredValidatorKeys = []): WorkflowTransitionRequestStatusEnum
    {
        if (WorkflowTransitionRequestPlaceEnum::CANCELLED === $this->place) {
            return WorkflowTransitionRequestStatusEnum::CANCELLED;
        }

        if (WorkflowTransitionRequestPlaceEnum::PUBLISHED === $this->place) {
            return WorkflowTransitionRequestStatusEnum::PUBLISHED;
        }

        if (WorkflowTransitionRequestPlaceEnum::BYPASSED === $this->place) {
            return WorkflowTransitionRequestStatusEnum::BYPASSED;
        }

        if ($this->countUserApprovals() < $requiredUserApprovals) {
            return WorkflowTransitionRequestStatusEnum::PENDING;
        }

        // People carry the request, validators can hold it back: one marked required keeps the
        // request pending until it approved, however many approvals it collected.
        foreach ($this->decisions as $decision) {
            $validatorKey = $decision->getValidatorKey();
            if (null === $validatorKey) {
                continue;
            }

            // Pending is not a rejection, but it is not an approval either, so a required
            // validator that has not answered yet still holds the request.
            if (!$decision->isApproved() && \in_array($validatorKey, $requiredValidatorKeys, true)) {
                return WorkflowTransitionRequestStatusEnum::PENDING;
            }
        }

        return WorkflowTransitionRequestStatusEnum::APPROVED;
    }

    public function countUserApprovals(): int
    {
        $approvals = 0;
        foreach ($this->decisions as $decision) {
            // Deleting a user nulls the row's user, leaving it owned by nobody. It stays as history
            // but must not keep voting.
            if ($decision->isUserDecision() && $decision->isApproved() && null !== $decision->getUser()) {
                ++$approvals;
            }
        }

        return $approvals;
    }

    /**
     * A deleted user's decision stays as history but stops counting, on both sides.
     */
    public function countUserRejections(): int
    {
        $rejections = 0;
        foreach ($this->decisions as $decision) {
            if ($decision->isUserDecision() && $decision->isRejected() && null !== $decision->getUser()) {
                ++$rejections;
            }
        }

        return $rejections;
    }

    public function getActiveKey(): ?string
    {
        return $this->activeKey;
    }

    /**
     * @experimental This is an experimental feature and may change in future releases.
     */
    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    /**
     * @return list<WorkflowTransitionRequestDecision>
     */
    public function getDecisions(): array
    {
        $decisions = \array_values($this->decisions->toArray());

        \usort(
            $decisions,
            static fn (WorkflowTransitionRequestDecision $a, WorkflowTransitionRequestDecision $b) => $a->getId() <=> $b->getId(),
        );

        return $decisions;
    }

    public function addValidatorDecision(string $validatorKey): void
    {
        if (null !== $this->getValidatorDecision($validatorKey)) {
            return;
        }

        $this->decisions->add(WorkflowTransitionRequestDecision::forValidator($this, $validatorKey));
    }

    public function getValidatorDecision(string $validatorKey): ?WorkflowTransitionRequestDecision
    {
        foreach ($this->decisions as $decision) {
            if ($validatorKey === $decision->getValidatorKey()) {
                return $decision;
            }
        }

        return null;
    }

    public function addApproval(UserInterface $user, WorkflowTransitionRequestDecisionMessage ...$messages): void
    {
        $this->decide($user, WorkflowTransitionRequestDecisionStatusEnum::APPROVED, \array_values($messages));
    }

    public function addRejection(UserInterface $user, WorkflowTransitionRequestDecisionMessage ...$messages): void
    {
        if ([] === $messages) {
            throw new \InvalidArgumentException('A rejecting reviewer must say what is wrong.');
        }

        $this->decide($user, WorkflowTransitionRequestDecisionStatusEnum::REJECTED, \array_values($messages));
    }

    public function getUserDecision(UserInterface $user): ?WorkflowTransitionRequestDecision
    {
        foreach ($this->decisions as $decision) {
            if ($decision->getUser() === $user) {
                return $decision;
            }
        }

        return null;
    }

    public function cancel(): void
    {
        $this->transitionTo(WorkflowTransitionRequestPlaceEnum::CANCELLED);
    }

    public function publish(): void
    {
        $this->transitionTo(WorkflowTransitionRequestPlaceEnum::PUBLISHED);
    }

    /**
     * Closes the request because the content was published without the approvals it asked for.
     */
    public function bypass(): void
    {
        $this->transitionTo(WorkflowTransitionRequestPlaceEnum::BYPASSED);
    }

    /**
     * @param list<WorkflowTransitionRequestDecisionMessage> $messages
     */
    private function decide(
        UserInterface $user,
        WorkflowTransitionRequestDecisionStatusEnum $status,
        array $messages,
    ): void {
        if (!$this->place->isOpen()) {
            throw new WorkflowTransitionRequestClosedException($this);
        }

        if ($this->getCreator() === $user) {
            throw new SelfReviewNotAllowedException($this);
        }

        $decision = $this->getUserDecision($user);
        if (null !== $decision) {
            $decision->settle($status, $messages, new \DateTimeImmutable());

            return;
        }

        $this->decisions->add(WorkflowTransitionRequestDecision::forUser($this, $user, $status, $messages));
    }

    private function transitionTo(WorkflowTransitionRequestPlaceEnum $toPlace): void
    {
        if (!$this->place->isOpen()) {
            return;
        }

        $this->place = $toPlace;
        $this->syncActiveKey();
    }

    private function syncActiveKey(): void
    {
        $this->activeKey = $this->place->isOpen()
            ? \sprintf('%s:%s:%s', $this->resourceKey, $this->resourceId, $this->locale)
            : null;
    }
}
