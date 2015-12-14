<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Entity;

/**
 * AnalyticKeyDomain.
 */
class AnalyticKeyDomain
{
    /**
     * @var string
     */
    private $url;

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return AnalyticKeyDomain
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
