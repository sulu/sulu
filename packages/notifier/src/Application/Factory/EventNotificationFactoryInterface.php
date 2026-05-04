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

namespace Sulu\Notifier\Application\Factory;

use Symfony\Component\Notifier\Notification\Notification;

interface EventNotificationFactoryInterface
{
    public function supports(object $event): bool;

    /**
     * @param list<string> $channels channels resolved from sulu_notifier config
     */
    public function create(object $event, array $channels): Notification;
}
