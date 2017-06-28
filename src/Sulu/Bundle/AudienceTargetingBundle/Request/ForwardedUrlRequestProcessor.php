<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Request;

use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Reads the host and path from the passed original request. Will override the values from the UrlRequestProcessor. Used
 * for the recognizing of page in the sub requests for the caching.
 */
class ForwardedUrlRequestProcessor implements RequestProcessorInterface
{
    /**
     * @var string
     */
    private $urlHeader;

    /**
     * @param string $urlHeader
     */
    public function __construct($urlHeader)
    {
        $this->urlHeader = $urlHeader;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestAttributes $requestAttributes)
    {
        if (!$request->headers->has($this->urlHeader)) {
            return new RequestAttributes();
        }

        $originalRequest = Request::create($request->headers->get($this->urlHeader));
        $host = $originalRequest->getHost();
        $port = $originalRequest->getPort();

        return new RequestAttributes(['host' => $host, 'port' => $port, 'path' => $originalRequest->getPathInfo()]);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(RequestAttributes $attributes)
    {
        return true;
    }
}
