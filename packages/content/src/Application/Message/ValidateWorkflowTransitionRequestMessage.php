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
 * Runs a request's validators; carries the id only so it survives serialization. Routing it to a
 * transport is the whole async switch, consumed via `bin/adminconsole messenger:consume`.
 */
final class ValidateWorkflowTransitionRequestMessage
{
    public function __construct(private readonly string $workflowTransitionRequestId)
    {
    }

    public function getWorkflowTransitionRequestId(): string
    {
        return $this->workflowTransitionRequestId;
    }
}
