<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer\Attributes;

use Symfony\Component\HttpFoundation\Request;

class SegmentRequestProcessor implements RequestProcessorInterface
{
    /**
     * @var string
     */
    private $segmentCookieName;

    public function __construct(string $segmentCookieName)
    {
        $this->segmentCookieName = $segmentCookieName;
    }

    public function process(Request $request, RequestAttributes $requestAttributes)
    {
        $attributes = [];
        $webspace = $requestAttributes->getAttribute('portalInformation')->getWebspace();

        $segmentKey = $request->cookies->get($this->segmentCookieName);
        $cookieSegment = $segmentKey ? $webspace->getSegment($segmentKey) : null;

        $attributes['segment'] = $cookieSegment ?? $webspace->getDefaultSegment();

        return new RequestAttributes($attributes);
    }

    public function validate(RequestAttributes $attributes)
    {
        return true;
    }
}
