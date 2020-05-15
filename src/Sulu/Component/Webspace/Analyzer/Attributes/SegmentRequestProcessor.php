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
    const SEGMENT_COOKIE = '_ss';

    public function process(Request $request, RequestAttributes $requestAttributes)
    {
        $attributes = [];

        $webspace = $requestAttributes->getAttribute('portalInformation')->getWebspace();
        $attributes['segment'] = $webspace->getDefaultSegment();

        return new RequestAttributes($attributes);
    }

    public function validate(RequestAttributes $attributes)
    {
        return true;
    }
}
