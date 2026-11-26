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

use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;

/**
 * @internal this interface is internal and should not be implemented or used in another context
 */
interface WorkflowTransitionRequestReviewBypassCheckerInterface
{
    /**
     * Whether publishing now skips the review the request still waits for.
     */
    public function isBypassingReview(WorkflowTransitionRequest $workflowTransitionRequest): bool;
}
