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

namespace Sulu\Content\Tests\Unit\Content\Application\WorkflowTransitionRequest\Action;

use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Content\Application\WorkflowTransitionRequest\Action\WorkflowTransitionRequestActionInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;

class StubWorkflowTransitionRequestAction implements WorkflowTransitionRequestActionInterface
{
    /**
     * @var list<array{WorkflowTransitionRequest, array<string, mixed>}>
     */
    public array $executed = [];

    /**
     * @param array<string, string> $labelParameters
     * @param array{message: string, parameters: array<string, string>}|null $statusMessage
     */
    public function __construct(
        private readonly bool $available = true,
        private readonly string $permission = PermissionTypes::LIVE,
        private readonly array $labelParameters = [],
        private readonly bool $replacesPublish = false,
        private readonly ?array $statusMessage = null,
    ) {
    }

    public static function getKey(): string
    {
        return 'stub';
    }

    public function getLabel(): string
    {
        return 'acme.stub';
    }

    public function getLabelParameters(WorkflowTransitionRequest $request): array
    {
        return $this->labelParameters;
    }

    public function getPermission(): string
    {
        return $this->permission;
    }

    public function getStatusMessage(WorkflowTransitionRequest $request): ?array
    {
        return $this->statusMessage;
    }

    public function replacesPublish(WorkflowTransitionRequest $request): bool
    {
        return $this->replacesPublish;
    }

    public function isAvailable(WorkflowTransitionRequest $request): bool
    {
        return $this->available;
    }

    public function execute(WorkflowTransitionRequest $request, array $data): void
    {
        $this->executed[] = [$request, $data];
    }
}
