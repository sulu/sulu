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

/**
 * One decision on a request, made either by a user or by an automated validator. Only user decisions
 * count towards the required approvals: a validator reports on the content, it never stands in for a
 * reviewer.
 *
 * @internal
 */
class WorkflowTransitionRequestDecision
{
    private string $id;

    private WorkflowTransitionRequest $workflowTransitionRequest;

    private WorkflowTransitionRequestDecisionTypeEnum $type;

    /**
     * `user:<id>` or `validator:<key>`, unique per request, so the database keeps one decision per
     * reviewer and one per validator without a partial index.
     */
    private string $subjectKey;

    private ?UserInterface $user = null;

    private ?string $validatorKey = null;

    private WorkflowTransitionRequestDecisionStatusEnum $status;

    /**
     * @var list<array{key: string|null, parameters: array<string, float|int|string>, text: string|null}>
     */
    private array $messages = [];

    private ?\DateTimeImmutable $decidedAt = null;

    private function __construct(
        WorkflowTransitionRequest $workflowTransitionRequest,
        WorkflowTransitionRequestDecisionTypeEnum $type,
        string $subjectKey,
        WorkflowTransitionRequestDecisionStatusEnum $status,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->workflowTransitionRequest = $workflowTransitionRequest;
        $this->type = $type;
        $this->subjectKey = $subjectKey;
        $this->status = $status;
    }

    /**
     * @param list<DecisionMessage> $messages
     */
    public static function forUser(
        WorkflowTransitionRequest $workflowTransitionRequest,
        UserInterface $user,
        WorkflowTransitionRequestDecisionStatusEnum $status,
        array $messages = [],
    ): self {
        $decision = new self(
            $workflowTransitionRequest,
            WorkflowTransitionRequestDecisionTypeEnum::USER,
            self::subjectKey(WorkflowTransitionRequestDecisionTypeEnum::USER, (string) $user->getId()),
            $status,
        );
        $decision->user = $user;
        $decision->setMessages($messages);
        $decision->decidedAt = new \DateTimeImmutable();

        return $decision;
    }

    public static function forValidator(WorkflowTransitionRequest $workflowTransitionRequest, string $validatorKey): self
    {
        $decision = new self(
            $workflowTransitionRequest,
            WorkflowTransitionRequestDecisionTypeEnum::VALIDATOR,
            self::subjectKey(WorkflowTransitionRequestDecisionTypeEnum::VALIDATOR, $validatorKey),
            WorkflowTransitionRequestDecisionStatusEnum::PENDING,
        );
        $decision->validatorKey = $validatorKey;

        return $decision;
    }

    /**
     * The prefix comes from the type, so the column and the `type` column can never disagree.
     */
    private static function subjectKey(WorkflowTransitionRequestDecisionTypeEnum $type, string $subject): string
    {
        return \sprintf('%s:%s', $type->value, $subject);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): WorkflowTransitionRequestDecisionTypeEnum
    {
        return $this->type;
    }

    public function isUserDecision(): bool
    {
        return WorkflowTransitionRequestDecisionTypeEnum::USER === $this->type;
    }

    public function isValidatorDecision(): bool
    {
        return WorkflowTransitionRequestDecisionTypeEnum::VALIDATOR === $this->type;
    }

    public function getSubjectKey(): string
    {
        return $this->subjectKey;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function getValidatorKey(): ?string
    {
        return $this->validatorKey;
    }

    public function getStatus(): WorkflowTransitionRequestDecisionStatusEnum
    {
        return $this->status;
    }

    public function isApproved(): bool
    {
        return WorkflowTransitionRequestDecisionStatusEnum::APPROVED === $this->status;
    }

    public function isPending(): bool
    {
        return WorkflowTransitionRequestDecisionStatusEnum::PENDING === $this->status;
    }

    public function isRejected(): bool
    {
        return WorkflowTransitionRequestDecisionStatusEnum::REJECTED === $this->status;
    }

    /**
     * @return list<DecisionMessage>
     */
    public function getMessages(): array
    {
        return \array_map(
            static fn (array $message) => DecisionMessage::fromArray($message),
            $this->messages,
        );
    }

    /**
     * @param list<DecisionMessage> $messages
     */
    private function setMessages(array $messages): void
    {
        $this->messages = \array_map(static fn (DecisionMessage $message) => $message->toArray(), $messages);
    }

    public function getDecidedAt(): ?\DateTimeImmutable
    {
        return $this->decidedAt;
    }

    public function resetToPending(): void
    {
        $this->status = WorkflowTransitionRequestDecisionStatusEnum::PENDING;
        $this->messages = [];
        $this->decidedAt = null;
    }

    /**
     * Also mirrors a settlement that already happened in the database onto the hydrated row, so an
     * inline run answers the same as a worker run.
     *
     * @param list<DecisionMessage> $messages
     */
    public function settle(
        WorkflowTransitionRequestDecisionStatusEnum $status,
        array $messages,
        \DateTimeImmutable $decidedAt,
    ): void {
        $this->status = $status;
        $this->setMessages($messages);
        $this->decidedAt = $decidedAt;
    }
}
