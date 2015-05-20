<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

use Sulu\Component\Util\ArrayableInterface;

class Url implements ArrayableInterface
{
    /**
     * The url itself.
     *
     * @var string
     */
    private $url;

    /**
     * The language to which the url leads.
     *
     * @var string
     */
    private $language;

    /**
     * The country to which the url leads.
     *
     * @var string
     */
    private $country;

    /**
     * The segment to which the url leads.
     *
     * @var string
     */
    private $segment;

    /**
     * The url to which this url redirects.
     *
     * @var string
     */
    private $redirect;

    /**
     * The analytics key for the given url.
     *
     * @var string
     */
    private $analyticsKey;

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
     * Returns the url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets the country to which this url leads.
     *
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Returns the country to which this url leads.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Sets the language to which this url leads.
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Returns the language to which this url leads.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Sets the segment to which this url leads.
     *
     * @param string $segment
     */
    public function setSegment($segment)
    {
        $this->segment = $segment;
    }

    /**
     * Returns the segment to which this url leads.
     *
     * @return string
     */
    public function getSegment()
    {
        return $this->segment;
    }

    /**
     * Sets the redirect for this url.
     *
     * @param string $redirect
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
    }

    /**
     * Returns the redirect url.
     *
     * @return string
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * Sets the analytics key for this url.
     *
     * @param string $analyticsKey
     */
    public function setAnalyticsKey($analyticsKey)
    {
        $this->analyticsKey = $analyticsKey;
    }

    /**
     * Returns the analytics key.
     *
     * @return string
     */
    public function getAnalyticsKey()
    {
        return $this->analyticsKey;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray($depth = null)
    {
        $res = array();
        $res['url'] = $this->getUrl();
        $res['language'] = $this->getLanguage();
        $res['country'] = $this->getCountry();
        $res['segment'] = $this->getSegment();
        $res['redirect'] = $this->getRedirect();
        $res['analyticsKey'] = $this->getAnalyticsKey();

        return $res;
    }
}
