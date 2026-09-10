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

use Sulu\Content\Domain\Exception\UnknownRequestWorkflowException;

/**
 * @internal this interface is internal and should not be implemented or used in another context
 */
interface RequestWorkflowRegistryInterface
{
    /**
     * @throws UnknownRequestWorkflowException when no workflow is registered for the given name
     */
    public function get(string $name): RequestWorkflow;

    public function has(string $name): bool;
}
