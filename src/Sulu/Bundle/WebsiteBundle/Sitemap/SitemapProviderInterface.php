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
 * Interface for a single provider.
 */
interface SitemapProviderInterface
{
    /**
     * Google limit for sitemap-url elements.
     */
    public const PAGE_SIZE = 50000;

    /**
     * Returns sitemap-entries.
     *
     * @param int $page
     * @param string $scheme
     * @param string $host
     *
     * @return SitemapUrl[]
     */
    public function build($page, $scheme, $host);

    /**
     * Get the sitemap of a provider.
     *
     * @param string $scheme
     * @param string $host
     *
     * @return Sitemap
     */
    public function createSitemap($scheme, $host);

    /**
     * Get the alias of the sitemap provider.
     *
     * @return string
     */
    public function getAlias();

    /**
     * Returns max-page.
     *
     * @param string $scheme
     * @param string $host
     *
     * @return int
     */
    public function getMaxPage($scheme, $host);
}
