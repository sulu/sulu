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

namespace Sulu\Content\Application\Message;

final class RetryWorkflowTransitionRequestValidationMessage
{
    public function __construct(
        private readonly string $workflowTransitionRequestId,
        private readonly string $validatorKey,
    ) {
    }

    public function getWorkflowTransitionRequestId(): string
    {
        return $this->workflowTransitionRequestId;
    }

    public function getValidatorKey(): string
    {
        return $this->validatorKey;
    }
}
