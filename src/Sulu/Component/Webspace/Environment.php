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

class Environment
{
    /**
     * The type of the environment (dev, staging, prod, ...).
     *
     * @var string
     */
    private $type;

    /**
     * The urls for this environment.
     *
     * @var Url[]
     */
    private $urls;

    /**
     * @var Url
     */
    private $mainUrl;

    /**
     * Sets the tye of this environment.
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Returns the type of this environment.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Adds a new url to this environment.
     *
     * @param $url Url The url to add
     */
    public function addUrl(Url $url)
    {
        $this->urls[] = $url;

        $url->setEnvironment($this->getType());

        if ($url->isMain() || !$this->mainUrl) {
            $this->setMainUrl($url);
        }
    }

    /**
     * Sets the main url.
     *
     * @param Url $url
     */
    private function setMainUrl(Url $url)
    {
        if (null !== $this->mainUrl) {
            $this->mainUrl->setMain(false);
        }

        $this->mainUrl = $url;
        $this->mainUrl->setMain(true);
    }

    /**
     * Returns main url.
     *
     * @return Url
     */
    public function getMainUrl()
    {
        return $this->mainUrl;
    }

    /**
     * Set the urls for this environment.
     *
     * @param \Sulu\Component\Webspace\Url[] $urls
     */
    public function setUrls($urls)
    {
        $this->urls = $urls;
    }

    /**
     * Returns the urls for this environment.
     *
     * @return \Sulu\Component\Webspace\Url[]
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($depth = null)
    {
        $res = [];
        $res['type'] = $this->getType();

        foreach ($this->getUrls() as $url) {
            $res['urls'][] = $url->toArray();
        }

        return $res;
    }
}
