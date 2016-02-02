<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Sitemap;

/**
 * Provides twig functions for sitemap.
 */
interface SitemapTwigExtensionInterface extends \Twig_ExtensionInterface
{
    /**
     * Returns prefixed resourcelocator with the url and locale.
     */
    public function sitemapUrlFunction($url, $locale = null, $webspaceKey = null);

    /**
     * Returns full sitemap of webspace and language from the content.
     */
    public function sitemapFunction($locale = null, $webspaceKey = null);
}
