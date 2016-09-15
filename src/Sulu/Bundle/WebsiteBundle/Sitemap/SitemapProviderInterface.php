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
 * Interface for a single provider.
 */
interface SitemapProviderInterface
{
    /**
     * Google limit for sitemap-url elements.
     */
    const PAGE_SIZE = 50000;

    /**
     * Returns sitemap-entries.
     *
     * @param int $page
     * @param string $portalKey
     * @param string $locale
     *
     * @return SitemapUrl[]
     */
    public function build($page, $portalKey, $locale);

    /**
     * Create sitemap.
     *
     * @param string $alias
     *
     * @return Sitemap
     */
    public function createSitemap($alias);

    /**
     * Returns max-page.
     *
     * @return int
     */
    public function getMaxPage();
}
