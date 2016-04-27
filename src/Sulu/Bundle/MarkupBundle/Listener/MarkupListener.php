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
     * @var array
     */
    private $mimeTypeFormatMap;

    /**
     * @param MarkupParserInterface[] $markupParser
     * @param array $mimeTypeFormatMap
     */
    public function __construct(
        array $markupParser,
        array $mimeTypeFormatMap = ['text/html' => 'html', 'application/xml' => 'xml', 'text/xml' => 'xml']
    ) {
        $this->markupParser = $markupParser;
        $this->mimeTypeFormatMap = $mimeTypeFormatMap;
    }

    /**
     * Parses content of response and set the replaced html as new content.
     *
     * @param FilterResponseEvent $event
     */
    public function replaceMarkup(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $mimeType = explode(';', $response->headers->get('Content-Type'))[0];
        if (!array_key_exists($mimeType, $this->mimeTypeFormatMap)
            || !array_key_exists($this->mimeTypeFormatMap[$mimeType], $this->markupParser)
        ) {
            return;
        }

        $format = $this->mimeTypeFormatMap[$mimeType];

        $response->setContent($this->markupParser[$format]->parse($response->getContent()));
    }
}
