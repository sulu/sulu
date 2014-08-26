<?php

namespace EventListener;

use Massive\Bundle\SearchBundle\Search\Event\HitEvent;

class HitListener
{
    public function __construct(RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->requestAnalyzer = $requestAnalyzer;
    }

    public function handleHit(HitEvent $event)
    {
        $document = $event->getHit()->getDocument();
        $url = sprintf('%s/%s', $this->requestAnalyzer->getCurrentResourceLocatorPrefix(), $document->getUrl());
        $document->setUrl($url);
    }
}
