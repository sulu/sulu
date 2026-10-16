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

/**
 * Runs one validator of a request; carries the ids only so it survives serialization. A request with
 * several validators sends one message each, so they can run side by side on several workers. Routing
 * it to a transport is the whole async switch, consumed via `bin/adminconsole messenger:consume`.
 */
final class ValidateWorkflowTransitionRequestMessage
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
