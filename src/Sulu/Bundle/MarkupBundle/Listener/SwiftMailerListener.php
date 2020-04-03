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
use Swift_Events_SendEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class SwiftMailerListener implements \Swift_Events_SendListener
{
    /**
     * @var MarkupParserInterface[]
     */
    private $markupParser;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param MarkupParserInterface[] $markupParser
     */
    public function __construct(array $markupParser, RequestStack $requestStack)
    {
        $this->markupParser = $markupParser;
        $this->requestStack = $requestStack;
    }

    public function beforeSendPerformed(Swift_Events_SendEvent $event)
    {
        $message = $event->getMessage();

        $body = $message->getBody();
        $format = $message->getBodyContentType();

        if (count($explodedFormat = explode('/', $format)) > 1) {
            $format = $explodedFormat[1];
        }

        $locale = $this->requestStack->getCurrentRequest()->getLocale();

        if (!$body || !array_key_exists($format, $this->markupParser)) {
            return;
        }

        $message->setbody(
            $this->markupParser[$format]->parse($body, $locale)
        );
    }

    public function sendPerformed(Swift_Events_SendEvent $evt)
    {
    }
}
