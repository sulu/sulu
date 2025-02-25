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

use Psr\Container\ContainerInterface;
use Sulu\Bundle\MarkupBundle\Markup\MarkupParserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Parses content of response and set the replaced html as new content.
 */
class MarkupListener implements EventSubscriberInterface
{
    public function __construct(private ContainerInterface $markupParser)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => ['replaceMarkup', -10]];
    }

    /**
     * Parses content of response and set the replaced html as new content.
     */
    public function replaceMarkup(ResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        /** @var string $format */
        $format = $request->getRequestFormat();

        $content = $response->getContent();

        if (!$content || !$this->markupParser->has($format)) {
            return;
        }

        /** @var MarkupParserInterface $markupParser */
        $markupParser = $this->markupParser->get($format);

        $response->setContent($markupParser->parse($content, $request->getLocale()));
    }
}
