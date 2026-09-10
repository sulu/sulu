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
 * @internal
 */
enum WorkflowTransitionRequestStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case CANCELLED = 'cancelled';
    case PUBLISHED = 'published';
    /** The workflow the request names is no longer configured, so nothing can be derived from it. */
    case UNKNOWN = 'unknown';
}
