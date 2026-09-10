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

namespace Sulu\Content\Application\RequestWorkflow\Validator;

use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;

final class ValidationContext
{
    /**
     * @param array<string, mixed> $validatorConfig
     */
    public function __construct(
        public readonly WorkflowTransitionRequest $request,
        public readonly array $validatorConfig,
    ) {
    }
}
