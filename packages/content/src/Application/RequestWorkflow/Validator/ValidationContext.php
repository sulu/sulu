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

/**
 * What a validator is told about the content it checks. It names the content rather than handing over
 * the request, so a validator can be tested without building one.
 */
final class ValidationContext
{
    /**
     * @param array<string, mixed> $validatorConfig
     */
    public function __construct(
        public readonly string $resourceKey,
        public readonly string $resourceId,
        public readonly string $locale,
        public readonly string $workflowName,
        public readonly array $validatorConfig,
    ) {
    }
}
