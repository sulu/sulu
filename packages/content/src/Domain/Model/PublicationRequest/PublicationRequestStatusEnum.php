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

namespace Sulu\Content\Domain\Model\PublicationRequest;

enum PublicationRequestStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case PUBLISHED = 'published';

    public function isActive(): bool
    {
        return \in_array($this, [self::PENDING, self::APPROVED], true);
    }

    public function isClosed(): bool
    {
        return \in_array($this, [self::CANCELLED, self::PUBLISHED], true);
    }
}
