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
 * Checks an existing request, optionally over the message bus. Never counts towards
 * `required_user_approvals`: a check reports on the content, it does not stand in for a reviewer.
 */
interface RequestWorkflowValidatorInterface
{
    /**
     * Name used in the config and on the check rows; unique, duplicates fail at compile time.
     * Static because the service locator indexes by it and stays lazy: only configured validators
     * are ever constructed.
     */
    public static function getKey(): string;

    /**
     * Must not mutate the request or write to the database; the caller owns the reviewer row and may
     * retry. Load anything else through own dependencies. Throwing is recorded as a rejection.
     */
    public function check(ValidationContext $context): ValidationResult;
}
