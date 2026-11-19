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
 * Dispatched while a cancel or reject transition runs, after the request is cancelled and before the
 * workflow writes its marking and the changes are flushed. It is not dispatched when the request is
 * deleted with its content or locale. A bundle that extended the request form reads its data here and
 * undoes what it set up. The event is not an activity, the cancellation itself is logged as a content
 * transition.
 *
 * @experimental This is an experimental feature and may change in future releases.
 */
final class WorkflowTransitionRequestCancelledEvent
{
    public function __construct(
        private readonly WorkflowTransitionRequest $workflowTransitionRequest,
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
     * What the requester filled into the request form.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->workflowTransitionRequest->getData();
    }
}
