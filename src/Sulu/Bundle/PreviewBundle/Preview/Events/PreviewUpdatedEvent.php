<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Events;

use Symfony\Contracts\EventDispatcher\Event;

class PreviewUpdatedEvent extends Event
{
    /**
     * @param mixed[] $payload
     */
    public function __construct(
        private array $payload
    ) {
    }

    public function getEventType(): string
    {
        return 'preview_updated';
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }
}
