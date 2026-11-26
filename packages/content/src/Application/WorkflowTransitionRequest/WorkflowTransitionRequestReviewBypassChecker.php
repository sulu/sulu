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

namespace Sulu\Content\Application\WorkflowTransitionRequest;

use Sulu\Content\Application\RequestWorkflow\WorkflowTransitionRequestStatusResolverInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Action\WorkflowTransitionRequestActionRegistryInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;

/**
 * @final
 *
 * @internal this class is internal and should not be extended from or used in another context
 */
class WorkflowTransitionRequestReviewBypassChecker implements WorkflowTransitionRequestReviewBypassCheckerInterface
{
    public function __construct(
        private readonly WorkflowTransitionRequestStatusResolverInterface $workflowTransitionRequestStatusResolver,
        private readonly WorkflowTransitionRequestActionRegistryInterface $workflowTransitionRequestActionRegistry,
    ) {
    }

    /**
     * A publish that an action of a bundle carries out, such as a scheduled one, is what the request
     * asked for, even while it waits for approvals.
     */
    public function isBypassingReview(WorkflowTransitionRequest $workflowTransitionRequest): bool
    {
        if (WorkflowTransitionRequestStatusEnum::APPROVED === $this->workflowTransitionRequestStatusResolver->resolve($workflowTransitionRequest)) {
            return false;
        }

        foreach ($this->workflowTransitionRequestActionRegistry->all() as $workflowTransitionRequestAction) {
            if ($workflowTransitionRequestAction->replacesPublish($workflowTransitionRequest)) {
                return false;
            }
        }

        return true;
    }
}
