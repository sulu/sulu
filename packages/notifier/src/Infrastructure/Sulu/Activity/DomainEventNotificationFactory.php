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

namespace Sulu\Notifier\Infrastructure\Sulu\Activity;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Notifier\Application\Factory\EventNotificationFactoryInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal no backwards compatibility promise is given for this class, create your own service
 *           implementing EventNotificationFactoryInterface instead
 */
final class DomainEventNotificationFactory implements EventNotificationFactoryInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
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
        if (!$event instanceof DomainEvent) {
            throw new \InvalidArgumentException(\sprintf(
                'Expected instance of "%s", "%s" given.',
                DomainEvent::class,
                $event::class,
            ));
        }

        $subjectKey = \sprintf('sulu_notifier.subject.%s.%s', $event->getResourceKey(), $event->getEventType());
        $contentKey = \sprintf('sulu_activity.description.%s.%s', $event->getResourceKey(), $event->getEventType());

        $user = $event->getUser()?->getFullName()
            ?? $this->translator->trans('sulu_activity.someone', [], 'admin', $this->locale);

        $parameters = [
            '{userFullName}' => $this->escapeForChat($user),
            '{resourceTitle}' => $this->escapeForChat($event->getResourceTitle() ?? ''),
            '{resourceLocale}' => $event->getResourceLocale() ?? '',
        ];

        foreach ($event->getEventContext() as $key => $value) {
            $parameters['{context_' . $key . '}'] = $this->escapeForChat($this->stringifyContextValue($value));
        }

        $subject = $this->translator->trans($subjectKey, $parameters, 'admin', $this->locale);
        $content = $this->translator->trans($contentKey, $parameters, 'admin', $this->locale);

        return (new Notification($subject, $channels))->content($content);
    }

    /**
     * Escapes characters with special meaning in chat markup (e.g. Slack mrkdwn),
     * so user-controlled content (resource titles, user names, event context)
     * cannot inject links, mentions or channel pings.
     *
     * @see https://api.slack.com/reference/surfaces/formatting#escaping
     */
    private function escapeForChat(string $value): string
    {
        return \str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $value);
    }

    private function stringifyContextValue(mixed $value): string
    {
        if (\is_string($value)) {
            return $value;
        }

        if (\is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        if (\is_array($value)) {
            return (string) \json_encode($value);
        }

        return \get_debug_type($value);
    }
}
