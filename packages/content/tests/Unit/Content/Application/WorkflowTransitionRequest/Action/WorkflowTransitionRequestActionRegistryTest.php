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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\WorkflowTransitionRequest\Action\WorkflowTransitionRequestActionRegistry;
use Symfony\Component\DependencyInjection\ServiceLocator;

#[CoversClass(WorkflowTransitionRequestActionRegistry::class)]
class WorkflowTransitionRequestActionRegistryTest extends TestCase
{
    public function testGetReturnsTheActionOfTheKey(): void
    {
        $action = new StubWorkflowTransitionRequestAction();
        $registry = $this->createRegistry(['stub' => $action]);

        $this->assertSame($action, $registry->get('stub'));
    }

    public function testGetReturnsNullForAnUnknownKey(): void
    {
        $this->assertNull($this->createRegistry(['stub' => new StubWorkflowTransitionRequestAction()])->get('unknown'));
    }

    public function testAllReturnsEveryAction(): void
    {
        $action = new StubWorkflowTransitionRequestAction();

        $this->assertSame([$action], $this->createRegistry(['stub' => $action])->all());
        $this->assertSame([], $this->createRegistry([])->all());
    }

    /**
     * @param array<string, StubWorkflowTransitionRequestAction> $actions
     */
    private function createRegistry(array $actions): WorkflowTransitionRequestActionRegistry
    {
        return new WorkflowTransitionRequestActionRegistry(new ServiceLocator(
            \array_map(static fn (StubWorkflowTransitionRequestAction $action) => static fn () => $action, $actions),
        ));
    }
}
