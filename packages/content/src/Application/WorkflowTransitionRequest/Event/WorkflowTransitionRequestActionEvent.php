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

namespace Sulu\Content\Application\WorkflowTransitionRequest\Event;

use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;

/**
 * Dispatched after an action on a workflow transition request. The content package knows no titles,
 * so a resource package turns this into its own domain event for the activity log.
 *
 * The context holds `comment` for approved and rejected (the reviewer's comment, null if none),
 * `validatorKey` for validation_retried and `approved` and `rejected` for validated (the counts over
 * all checks of the request).
 *
 * Validated events carry no current user when a worker runs the checks, but the editor's user when
 * the checks run inline in the request that sent the content for review.
 *
 * @experimental This is an experimental feature and may change in future releases.
 */
final class WorkflowTransitionRequestActionEvent
{
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const VALIDATION_RETRIED = 'validation_retried';
    public const VALIDATED = 'validated';

    /**
     * @param array<string, scalar|null> $context
     */
    public function __construct(
        private readonly WorkflowTransitionRequest $workflowTransitionRequest,
        private readonly string $action,
        private readonly array $context = [],
    ) {
    }

    public function getResourceKey(): string
    {
        return $this->workflowTransitionRequest->getResourceKey();
    }

    public function getResourceId(): string
    {
        return $this->workflowTransitionRequest->getResourceId();
    }

    public function getLocale(): string
    {
        return $this->workflowTransitionRequest->getLocale();
    }

    /**
     * @internal the request entity is not part of the public surface, use the getters above
     */
    public function getWorkflowTransitionRequest(): WorkflowTransitionRequest
    {
        return $this->workflowTransitionRequest;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Whether the action only wrote through DBAL statements, so no flush follows that would store an
     * activity collected for it. Such an activity has to be dispatched right away.
     */
    public function isWrittenWithoutFlush(): bool
    {
        return self::VALIDATED === $this->action;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
