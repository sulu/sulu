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

namespace Sulu\Content\Application\WorkflowTransitionRequest;

use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;

/**
 * Builds the single canonical array representation of a {@see WorkflowTransitionRequest} shared by the
 * content normalizer (form view) and the admin controller (by-id), including every reviewer row,
 * people and validators alike.
 *
 * @internal this interface is internal and should not be implemented or used in another context
 */
interface WorkflowTransitionRequestViewFactoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function build(WorkflowTransitionRequest $request): array;
}
