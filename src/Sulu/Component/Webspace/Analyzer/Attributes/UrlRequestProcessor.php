<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer\Attributes;

use Symfony\Component\HttpFoundation\Request;

/**
 * Reads the host and path from the request and writes them into the request attributes.
 */
class UrlRequestProcessor implements RequestProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestAttributes $requestAttributes)
    {
        $host = $request->getHost();
        $port = $request->getPort();

        return new RequestAttributes(['host' => $host, 'port' => $port, 'path' => $request->getPathInfo()]);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(RequestAttributes $attributes)
    {
        return true;
    }
}
