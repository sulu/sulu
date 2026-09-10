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
 * The persisted part of a request's state. Everything else is derived from the reviewer rows by
 * {@see WorkflowTransitionRequest::getStatus()}.
 */
enum WorkflowTransitionRequestLifecycleEnum: string
{
    case OPEN = 'open';
    case CANCELLED = 'cancelled';
    case PUBLISHED = 'published';

    public function isOpen(): bool
    {
        return self::OPEN === $this;
    }
}
