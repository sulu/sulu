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

namespace Sulu\Notifier\Tests\Application\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

class TestDomainEvent extends DomainEvent
{
    public function __construct(
        private readonly string $resourceKey = 'test_resource',
        private readonly string $eventType = 'created',
        private readonly ?string $title = 'A great song will win',
    ) {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function getResourceId(): string
    {
        return '1';
    }

    public function getResourceTitle(): ?string
    {
        return $this->title;
    }
}
