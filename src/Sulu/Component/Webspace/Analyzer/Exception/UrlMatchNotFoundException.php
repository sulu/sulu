<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Thrown by request analyzer, when there is no portal matching the given URL.
 */
class UrlMatchNotFoundException extends HttpException
{
    /**
     * The url for which no portal exists.
     *
     * @var string
     */
    private $url;

    /**
     * @param string[] $portalUrls
     */
    public function __construct($url, array $portalUrls = [])
    {
        $this->url = $url;
        $message = 'There exists no portal for the URL "' . $url . '"';

        if (!empty($portalUrls)) {
            $message .= ', the URL should begin with one of the following Portal URLs: "' . \implode('", "', $portalUrls) . '"';
        }

        parent::__construct(404, $message);
    }

    /**
     * Returns the url for which no portal exists.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
