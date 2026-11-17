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

final class ExecuteWorkflowTransitionRequestActionMessage
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly string $workflowTransitionRequestId,
        private readonly string $actionKey,
        private readonly array $data = [],
    ) {
    }

    public function getWorkflowTransitionRequestId(): string
    {
        return $this->workflowTransitionRequestId;
    }

    public function getActionKey(): string
    {
        return $this->actionKey;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
