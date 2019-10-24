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
 * Interface for sitemap-renderer.
 */
interface XmlSitemapRendererInterface
{
    /**
     * Render sitemap-index.
     *
     * If returns null there is no index available.
     *
     * @param string $scheme
     * @param string $host
     *
     * @return null|string
     */
    public function renderIndex($scheme, $host);

    /**
     * Render sitemap for a given alias.
     *
     * If returns null there is no sitemap available.
     *
     * @param string $alias
     * @param int $page
     * @param string $host
     * @param string $scheme
     *
     * @return null|string
     */
    public function renderSitemap($alias, $page, $scheme, $host);
}
