<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Tests\Application\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

class TestDomainEvent extends DomainEvent
{
    public function getEventType(): string
    {
        return 'test';
    }

    public function getResourceKey(): string
    {
        return 'test';
    }

    public function getResourceId(): string
    {
        return 'test';
    }
}
