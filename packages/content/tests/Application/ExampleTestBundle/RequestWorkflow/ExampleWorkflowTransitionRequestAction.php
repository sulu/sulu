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
 * An action of a bundle for the tests, which records what it was sent.
 */
class ExampleWorkflowTransitionRequestAction implements WorkflowTransitionRequestActionInterface
{
    /**
     * @var list<array<string, mixed>>
     */
    public static array $executed = [];

    public static function getKey(): string
    {
        return 'example_note';
    }

    public function getLabel(): string
    {
        return 'example_test.note';
    }

    public function getPermission(): string
    {
        return PermissionTypes::EDIT;
    }

    public function isAvailable(WorkflowTransitionRequest $request): bool
    {
        return true;
    }

    public function execute(WorkflowTransitionRequest $request, array $data): void
    {
        self::$executed[] = $data;
    }
}
