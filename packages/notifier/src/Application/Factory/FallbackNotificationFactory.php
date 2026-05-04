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
use Symfony\Contracts\Translation\TranslatorInterface;

final class FallbackNotificationFactory implements EventNotificationFactoryInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly string $locale,
    ) {
    }

    public function supports(object $event): bool
    {
        return true;
    }

    /**
     * @param list<string> $channels
     */
    public function create(object $event, array $channels): Notification
    {
        $shortClass = (new \ReflectionClass($event))->getShortName();

        $subject = $this->translator->trans(
            'sulu_notifier.fallback.subject',
            ['%class%' => $shortClass],
            'admin',
            $this->locale,
        );

        $content = $this->translator->trans(
            'sulu_notifier.fallback.content',
            ['%class%' => $shortClass],
            'admin',
            $this->locale,
        );

        return (new Notification($subject, $channels))->content($content);
    }
}
