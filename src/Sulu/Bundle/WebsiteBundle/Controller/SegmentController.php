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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
        $url = $request->query->get('url', '');

        // only a url with the same host is supported in the segment switcher
        if (0 !== \strpos($url, $request->getSchemeAndHttpHost())
            && 0 !== \strpos($url, '/')
        ) {
            throw new BadRequestHttpException(\sprintf(
                'The given "url" query parameter with value "%s" is not supported.',
                $url
            ));
        }

        $webspace = $this->requestAnalyzer->getWebspace();
        $defaultSegment = $webspace->getDefaultSegment();
        $segmentKey = $request->query->get('segment');

        if (!$webspace->getSegment($segmentKey)) {
            $segmentKey = null;
        }

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
