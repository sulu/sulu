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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\AdminBundle\Admin\View\ResourceViewUrlGeneratorInterface;
use Sulu\Bundle\AdminBundle\Exception\ResourceViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewParameterNotFoundException;
use Sulu\Notifier\Application\Factory\EventNotificationFactoryInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal no backwards compatibility promise is given for this class, create your own service
 *           implementing EventNotificationFactoryInterface instead
 */
final class DomainEventNotificationFactory implements EventNotificationFactoryInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly string $locale,
        private readonly ?ResourceViewUrlGeneratorInterface $resourceViewUrlGenerator = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
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

        $link = $this->resolveLink($event);

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

        return (new Notification($subject, $channels))->content($this->appendLink($content, $link));
    }

    private function resolveLink(DomainEvent $event): ?string
    {
        if (null === $this->resourceViewUrlGenerator) {
            // sulu_admin.resource_view_url_generator is admin-context only (sulu.context tag).
            return null;
        }

        $viewParameters = ['id' => $event->getResourceId()];

        if (null !== ($webspaceKey = $event->getResourceWebspaceKey())) {
            $viewParameters['webspace'] = $webspaceKey;
        }

        if (null !== ($locale = $event->getResourceLocale())) {
            $viewParameters['locale'] = $locale;
        }

        try {
            return $this->resourceViewUrlGenerator->generate(
                $event->getResourceKey(),
                'detail',
                $viewParameters,
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
        } catch (ResourceViewNotFoundException) {
            // No detail view configured for this resource — an expected gap, not worth logging.
            return null;
        } catch (ViewNotFoundException) {
            // Most commonly caused by the current user lacking access to the view.
            $this->logger->info('sulu_notifier could not resolve a deep link: view not found', [
                'resourceKey' => $event->getResourceKey(),
                'resourceId' => $event->getResourceId(),
            ]);

            return null;
        } catch (ViewParameterNotFoundException $exception) {
            $this->logger->warning('sulu_notifier could not resolve a deep link: missing view parameter', [
                'resourceKey' => $event->getResourceKey(),
                'resourceId' => $event->getResourceId(),
                'parameter' => $exception->getParameter(),
            ]);

            return null;
        }
    }

    private function appendLink(string $content, ?string $link): string
    {
        if (null === $link) {
            return $content;
        }

        return $content . "\n\n" . $link;
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
