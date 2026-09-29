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

    public function getWorkflowTransitionRequest(): WorkflowTransitionRequest
    {
        return $this->workflowTransitionRequest;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
