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

namespace Sulu\Content\Domain\Model\WorkflowTransitionRequest;

/**
 * Names which subject decided, and therefore which column of the row is filled.
 *
 * @internal
 */
enum WorkflowTransitionRequestDecisionTypeEnum: string
{
    case USER = 'user';
    case VALIDATOR = 'validator';
}
