<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class SegmentController
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var string
     */
    private $segmentCookieName;

    public function __construct(RequestAnalyzerInterface $requestAnalyzer, string $segmentCookieName)
    {
        $this->requestAnalyzer = $requestAnalyzer;
        $this->segmentCookieName = $segmentCookieName;
    }

    public function switchAction(Request $request)
    {
        $webspace = $this->requestAnalyzer->getWebspace();
        $segments = $webspace->getSegments();
        $defaultSegment = $webspace->getDefaultSegment();

        $url = $request->query->get('url');
        $segmentKey = $request->query->get('segment');

        $response = new RedirectResponse($url);
        $response->headers->setCookie(
            Cookie::create(
                $this->segmentCookieName,
                $defaultSegment && $defaultSegment->getKey() === $segmentKey ? null : $segmentKey
            )
        );

        return $response;
    }
}
