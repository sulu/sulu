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
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;

/**
 * Derives the status of a request. An action of a bundle may use it, for example to check that a request is approved.
 *
 * @experimental This is an experimental feature and may change in future releases.
 */
interface WorkflowTransitionRequestStatusResolverInterface
{
    /**
     * @experimental This is an experimental feature and may change in future releases.
     */
    public function resolve(WorkflowTransitionRequest $workflowTransitionRequest): WorkflowTransitionRequestStatusEnum;
}
