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

use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;

/**
 * Derives a request's status from the workflow config it names. Nothing is stored on the row, so a
 * config change applies to open requests as well.
 *
 * @internal
 */
final class WorkflowTransitionRequestStatusResolver implements WorkflowTransitionRequestStatusResolverInterface
{
    public function __construct(
        private readonly RequestWorkflowRegistryInterface $registry,
    ) {
    }

    public function resolve(WorkflowTransitionRequest $workflowTransitionRequest): WorkflowTransitionRequestStatusEnum
    {
        $workflowName = $workflowTransitionRequest->getWorkflowName();

        // A workflow can be renamed or removed while requests are open. Answering `unknown` keeps a
        // list of requests readable; cancelling stays possible, so the content is never stuck.
        if (!$this->registry->has($workflowName)) {
            return WorkflowTransitionRequestStatusEnum::UNKNOWN;
        }

        $workflow = $this->registry->get($workflowName);

        return $workflowTransitionRequest->getStatus(
            $workflow->getRequiredUserApprovals(),
            $workflow->getRequiredValidatorKeys(),
        );
    }
}
