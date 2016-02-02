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

interface SitemapGeneratorInterface
{
    /**
     * Generates a sitemap over all languages in webspace.
     *
     * @param string $webspaceKey
     * @param bool   $flat
     *
     * @return WebspaceSitemap
     */
    public function generateAllLocals($webspaceKey, $flat = false);

    /**
     * Generates a sitemap for given webspace.
     *
     * @param string $webspaceKey
     * @param string $locale
     * @param bool   $flat
     *
     * @return WebspaceSitemap
     */
    public function generate($webspaceKey, $locale, $flat = false);
}
