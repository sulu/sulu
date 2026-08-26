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

namespace Sulu\Notifier\Application\Notifier;

use Psr\Log\LoggerInterface;
use Sulu\Notifier\Application\Factory\EventNotificationFactoryInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\NoRecipient;

class EventNotifier
{
    /**
     * @param iterable<EventNotificationFactoryInterface> $factories sorted by tag priority desc
     * @param array<class-string, list<string>> $eventChannelMap
     */
    public function __construct(
        private readonly iterable $factories,
        private readonly NotifierInterface $symfonyNotifier,
        private readonly array $eventChannelMap,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function notify(object $event): void
    {
        $channels = $this->eventChannelMap[$event::class] ?? null;
        if (null === $channels) {
            return;
        }

        foreach ($this->factories as $factory) {
            if (!$factory->supports($event)) {
                continue;
            }

            try {
                $notification = $factory->create($event, $channels);
                $this->symfonyNotifier->send($notification, new NoRecipient());
            } catch (\Throwable $exception) {
                $this->logger->error('sulu_notifier dispatch failed', [
                    'event' => $event::class,
                    'exception' => $exception,
                ]);
            }

            return;
        }
    }
}
