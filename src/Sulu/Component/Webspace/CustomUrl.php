<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

use Sulu\Component\Util\ArrayableInterface;

/**
 * Contains information about custom-url.
 */
class CustomUrl implements ArrayableInterface
{
    /**
     * The url itself.
     *
     * @var string
     */
    private $url;

    public function __construct($url = null)
    {
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets the url.
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($depth = null)
    {
        return ['url' => $this->getUrl()];
    }
}
