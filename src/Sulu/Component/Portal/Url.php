<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Portal;


class Url
{
    /**
     * Weather this url is a main url or not
     * @var boolean
     */
    private $main;

    /**
     * The url itself
     * @var string
     */
    private $url;

    /**
     * Sets if this url is a main url or not
     * @param boolean $main True if it should be a main url, otherwise false
     */
    public function setMain($main)
    {
        $this->main = $main;
    }

    /**
     * Returns if this url is a main url
     * @return boolean
     */
    public function isMain()
    {
        return $this->main;
    }

    /**
     * Sets the url
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Returns the url
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
