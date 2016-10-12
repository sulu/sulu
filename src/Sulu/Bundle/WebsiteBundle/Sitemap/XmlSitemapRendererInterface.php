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

use Sulu\Component\Webspace\Portal;

/**
 * Interface for sitemap-renderer.
 */
interface XmlSitemapRendererInterface
{
    /**
     * Returns path of sitemap-index.
     *
     * @param string $scheme
     * @param string $webspaceKey
     * @param string $locale
     * @param string $url
     *
     * @return string
     */
    public function getIndexDumpPath($scheme, $webspaceKey, $locale, $url);

    /**
     * Render sitemap-index.
     *
     * If returns null there is no index available.
     *
     * @param string $domain if null current will be used
     * @param string $scheme if null current will be used
     *
     * @return null|string
     */
    public function renderIndex($domain = null, $scheme = null);

    /**
     * Returns path of sitemap.
     *
     * @param string $scheme
     * @param string $webspaceKey
     * @param string $locale
     * @param string $url
     * @param string $alias
     * @param int $page
     *
     * @return string
     */
    public function getDumpPath($scheme, $webspaceKey, $locale, $url, $alias, $page);

    /**
     * Render sitemap for a given alias.
     *
     * If returns null there is no sitemap available.
     *
     * @param string $alias
     * @param int $page
     * @param string $locale
     * @param Portal $portal
     * @param string $host
     * @param string $scheme
     *
     * @return null|string
     */
    public function renderSitemap($alias, $page, $locale, Portal $portal, $host, $scheme);
}
