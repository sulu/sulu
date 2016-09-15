<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Sitemap;

/**
 * Contains information for a alternate-link.
 */
class SitemapAlternateLink
{
    /**
     * Link.
     *
     * @var string
     */
    private $href;

    /**
     * Locale for alternate-link.
     *
     * @var string
     */
    private $locale;

    /**
     * @param string $locale
     * @param string $href
     */
    public function __construct($href, $locale)
    {
        $this->href = $href;
        $this->locale = $locale;
    }

    /**
     * Returns href.
     *
     * @return string
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * Returns locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }
}
