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
     * Render sitemap-index.
     *
     * If returns null there is no index available.
     *
     * @return string|null
     */
    public function renderIndex();

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
