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

namespace Sulu\Content\Application\WorkflowTransitionRequest\Action;

/**
 * @internal this interface is internal and should not be implemented or used in another context
 */
interface WorkflowTransitionRequestActionRegistryInterface
{
    /**
     * @return list<WorkflowTransitionRequestActionInterface>
     */
    public function all(): array;

    public function get(string $key): ?WorkflowTransitionRequestActionInterface;
}
