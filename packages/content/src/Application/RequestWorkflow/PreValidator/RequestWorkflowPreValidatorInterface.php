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

namespace Sulu\Content\Application\RequestWorkflow\PreValidator;

use Sulu\Content\Application\RequestWorkflow\Validator\ValidationResult;

/**
 * A synchronous rule content must pass before it may go live; a failure aborts the transition.
 */
interface RequestWorkflowPreValidatorInterface
{
    /**
     * Name used in the workflow config; unique across pre-validators, duplicates fail at compile time.
     * Static because the service locator indexes by it and stays lazy: only configured pre-validators
     * are ever constructed.
     */
    public static function getKey(): string;

    /**
     * Must not mutate the content or cause side effects.
     */
    public function check(PreValidationContext $context): ValidationResult;
}
