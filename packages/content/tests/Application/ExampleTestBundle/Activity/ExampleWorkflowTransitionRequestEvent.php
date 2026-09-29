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

namespace Sulu\Content\Tests\Application\ExampleTestBundle\Activity;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;

/**
 * Stands in for the domain event a resource package such as pages emits for a request action.
 */
final class ExampleWorkflowTransitionRequestEvent extends DomainEvent
{
    /**
     * @param array<string, scalar|null> $context
     */
    public function __construct(
        private readonly WorkflowTransitionRequest $workflowTransitionRequest,
        private readonly string $action,
        private readonly array $context,
    ) {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'workflow_transition_request.' . $this->action;
    }

    public function getEventContext(): array
    {
        return $this->context;
    }

    public function getResourceKey(): string
    {
        return Example::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return $this->workflowTransitionRequest->getResourceId();
    }

    public function getResourceLocale(): string
    {
        return $this->workflowTransitionRequest->getLocale();
    }
}
