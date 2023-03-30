<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Listener;

use Sulu\Bundle\MarkupBundle\Markup\MarkupParserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;

class MailerListener implements EventSubscriberInterface
{
    private \Sulu\Bundle\MarkupBundle\Markup\MarkupParserInterface $markupParser;

    private \Symfony\Component\HttpFoundation\RequestStack $requestStack;

    private string $defaultLocale;

    public function __construct(MarkupParserInterface $markupParser, RequestStack $requestStack, string $defaultLocale)
    {
        $this->markupParser = $markupParser;
        $this->requestStack = $requestStack;
        $this->defaultLocale = $defaultLocale;
    }

    public static function getSubscribedEvents()
    {
        return [
            MessageEvent::class => 'onMessage',
        ];
    }

    public function onMessage(MessageEvent $event): void
    {
        $message = $event->getMessage();
        if (!$message instanceof Email) {
            return;
        }

        $html = $message->getHtmlBody();

        if (!$html || !\is_string($html)) {
            return;
        }

        $currentRequest = $this->requestStack->getCurrentRequest();
        if (null !== $currentRequest) {
            $locale = $currentRequest->getLocale();
        } else {
            $locale = $this->defaultLocale;
        }

        $message->html($this->markupParser->parse($html, $locale));
    }
}
