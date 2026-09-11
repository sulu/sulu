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

namespace Sulu\Content\Domain\Value\WorkflowTransitionRequest;

/**
 * Names which subject decided, and therefore which column of the row is filled.
 */
enum WorkflowTransitionRequestDecisionTypeEnum: string
{
    case USER = 'user';
    case VALIDATOR = 'validator';
}
