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

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DomainEventNotificationFactory implements EventNotificationFactoryInterface
{
    public function __construct(
        private readonly TranslatorInterface&TranslatorBagInterface $translator,
        private readonly string $locale,
    ) {
    }

    public function supports(object $event): bool
    {
        return $event instanceof DomainEvent;
    }

    /**
     * @param list<string> $channels
     */
    public function create(object $event, array $channels): Notification
    {
        \assert($event instanceof DomainEvent);

        $subjectKey = \sprintf('sulu_notifier.subject.%s.%s', $event->getResourceKey(), $event->getEventType());
        $contentKey = \sprintf('sulu_activity.description.%s.%s', $event->getResourceKey(), $event->getEventType());

        $catalogue = $this->translator->getCatalogue($this->locale);

        if (!$catalogue->has($subjectKey, 'admin') || !$catalogue->has($contentKey, 'admin')) {
            $shortClass = (new \ReflectionClass($event))->getShortName();

            return (new Notification(
                $this->translator->trans('sulu_notifier.fallback.subject', ['%class%' => $shortClass], 'admin', $this->locale),
                $channels,
            ))->content(
                $this->translator->trans('sulu_notifier.fallback.content', ['%class%' => $shortClass], 'admin', $this->locale),
            );
        }

        $user = $event->getUser()?->getFullName()
            ?? $this->translator->trans('sulu_activity.someone', [], 'admin', $this->locale);

        $parameters = [
            '{userFullName}' => $user,
            '{resourceTitle}' => $event->getResourceTitle() ?? '',
            '{resourceLocale}' => $event->getResourceLocale() ?? '',
        ];

        foreach ($event->getEventContext() as $key => $value) {
            $parameters['{context_' . $key . '}'] = $value;
        }

        $subject = $this->translator->trans($subjectKey, $parameters, 'admin', $this->locale);
        $content = $this->translator->trans($contentKey, $parameters, 'admin', $this->locale);

        return (new Notification($subject, $channels))->content($content);
    }
}
