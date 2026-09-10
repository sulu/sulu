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

namespace Sulu\Content\Tests\Traits;

use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

trait ConsumeTransportTrait
{
    private function consumeTransport(string $transportName): void
    {
        $bus = static::getContainer()->get(MessageBusInterface::class);

        /** @var InMemoryTransport $transport */
        $transport = static::getContainer()->get('messenger.transport.' . $transportName);

        foreach ($transport->get() as $envelope) {
            // The received stamp makes the bus handle the message instead of sending it to the
            // transport again; no worker is started, so validators run on the synchronous path.
            $bus->dispatch($envelope->with(new ReceivedStamp($transportName)));
            $transport->ack($envelope);
        }
    }
}
