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

namespace Sulu\Content\Tests\Application\ExampleTestBundle\RequestWorkflow;

use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Content\Application\WorkflowTransitionRequest\Action\WorkflowTransitionRequestActionInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;

/**
 * An action of a bundle for the tests, which records the requests it ran on.
 */
class ExampleWorkflowTransitionRequestAction implements WorkflowTransitionRequestActionInterface
{
    /**
     * @var list<string> the ids of the requests the action ran on
     */
    public static array $executed = [];

    /**
     * Lets a test switch on the takeover of the publish, which the action of a bundle may ask for.
     */
    public static bool $replacesPublish = false;

    public static function getKey(): string
    {
        return 'example_note';
    }

    public function getLabel(): string
    {
        return 'example_test.note';
    }

    public function getLabelParameters(WorkflowTransitionRequest $request): array
    {
        return [];
    }

    public function getPermission(): string
    {
        return PermissionTypes::EDIT;
    }

    public function getStatusMessage(WorkflowTransitionRequest $request): ?array
    {
        return null;
    }

    public function replacesPublish(WorkflowTransitionRequest $request): bool
    {
        return self::$replacesPublish;
    }

    public function isAvailable(WorkflowTransitionRequest $request): bool
    {
        return true;
    }

    public function execute(WorkflowTransitionRequest $request): void
    {
        self::$executed[] = $request->getId();
    }
}
