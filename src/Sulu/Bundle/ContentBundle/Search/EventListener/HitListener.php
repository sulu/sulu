<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Search\EventListener;

use Massive\Bundle\SearchBundle\Search\Event\HitEvent;
use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * Listen to for new hits. If document instance of structure
 * prefix the current resource locator prefix to the URL.
 */
class HitListener
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    public function __construct(RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * Prefix url of document with current resourcelocator prefix.
     *
     * @param HitEvent $event
     */
    public function onHit(HitEvent $event)
    {
        if (false === $event->getMetadata()->reflection->isSubclassOf(BasePageDocument::class)) {
            return;
        }

        $document = $event->getHit()->getDocument();
        if ('/' !== $document->getUrl()[0]) {
            // is absolute URL

            return;
        }

        $url = sprintf(
            '%s/%s',
            rtrim($this->requestAnalyzer->getResourceLocatorPrefix(), '/'),
            ltrim($document->getUrl(), '/')
        );

        $document->setUrl($url);
    }
}
