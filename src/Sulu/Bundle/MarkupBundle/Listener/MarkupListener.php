<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Listener;

use Sulu\Bundle\MarkupBundle\Markup\MarkupParserInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Parses content of response and set the replaced html as new content.
 */
class MarkupListener
{
    /**
     * @var MarkupParserInterface
     */
    private $markupParser;

    /**
     * @param MarkupParserInterface[] $markupParser
     */
    public function __construct(array $markupParser)
    {
        $this->markupParser = $markupParser;
    }

    /**
     * Parses content of response and set the replaced html as new content.
     *
     * @param FilterResponseEvent $event
     */
    public function replaceMarkup(FilterResponseEvent $event)
    {
        $format = $event->getRequest()->getRequestFormat(null);
        if (!array_key_exists($format, $this->markupParser)) {
            return;
        }

        $response = $event->getResponse();
        $response->setContent(
            $this->markupParser[$format]->parse($response->getContent(), $event->getRequest()->getLocale())
        );
    }
}
