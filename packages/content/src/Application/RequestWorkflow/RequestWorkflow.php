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

namespace Sulu\Content\Application\RequestWorkflow;

use Sulu\Content\Application\RequestWorkflow\PreValidator\RequestWorkflowPreValidatorInterface;
use Sulu\Content\Application\RequestWorkflow\Validator\RequestWorkflowValidatorInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;

/**
 * Resolved configuration for a single named workflow, built by {@see RequestWorkflowRegistry}
 * from the bundle config tree.
 */
final class RequestWorkflow
{
    public const DEFAULT_NAME = WorkflowTransitionRequest::DEFAULT_WORKFLOW_NAME;

    /**
     * Reserved: a template tagged with this name opts out of review, so no workflow may carry it.
     */
    public const NONE_NAME = 'none';

    /**
     * @param array<string, array{validator: RequestWorkflowValidatorInterface, config: array<string, mixed>, blocking: bool}> $validators keyed by the validator's tag key, which is also its key on the check rows
     * @param list<string> $resources resource keys this workflow covers as the implicit default; empty means all, only the `default` workflow reads it
     * @param array<string, array{pre_validator: RequestWorkflowPreValidatorInterface, config: array<string, mixed>}> $preValidators sync rules that must pass before the content goes live, on request and on publish alike, keyed by tag key
     */
    public function __construct(
        public readonly string $name,
        public readonly array $validators,
        private readonly int $requiredHumanApprovalCount,
        public readonly array $resources = [],
        public readonly array $preValidators = [],
    ) {
    }

    public function appliesToResource(string $resourceKey): bool
    {
        return [] === $this->resources || \in_array($resourceKey, $this->resources, true);
    }

    public function getRequiredHumanApprovalCount(): int
    {
        return $this->requiredHumanApprovalCount;
    }
}
