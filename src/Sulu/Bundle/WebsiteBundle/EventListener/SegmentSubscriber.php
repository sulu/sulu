<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventListener;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SegmentSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $segmentHeader;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    public function __construct(string $segmentHeader, RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->segmentHeader = $segmentHeader;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => [
                ['addVaryHeader'],
            ],
        ];
    }

    public function addVaryHeader(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $webspace = $this->requestAnalyzer->getWebspace();

        if ($webspace && count($webspace->getSegments())) {
            $response->setVary($this->segmentHeader, false);
        }
    }
}
