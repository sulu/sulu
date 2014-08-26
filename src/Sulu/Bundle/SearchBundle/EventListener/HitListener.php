<?php

namespace Sulu\Bundle\SearchBundle\EventListener;

use Massive\Bundle\SearchBundle\Search\Event\HitEvent;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * Listen to for new hits. If document instance of structure
 * prefix the current resource locator prefix to the URL
 */
class HitListener
{
    public function __construct(RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->requestAnalyzer = $requestAnalyzer;
    }

    public function onHit(HitEvent $event)
    {
        if (false === $event->getDocumentReflection()->isSubclassOf('Sulu\Component\Content\Structure')) {
            return;
        }

        $document = $event->getHit()->getDocument();
        $url = sprintf('%s/%s', $this->requestAnalyzer->getCurrentResourceLocatorPrefix(), $document->getUrl());
        $document->setUrl($url);
    }
}
