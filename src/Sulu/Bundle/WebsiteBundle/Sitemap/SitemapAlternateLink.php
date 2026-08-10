<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
     * @param string $href Link
     * @param string $locale Locale for the alternate-link
     */
    public function __construct(
        private $href,
        private $locale
    ) {
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
