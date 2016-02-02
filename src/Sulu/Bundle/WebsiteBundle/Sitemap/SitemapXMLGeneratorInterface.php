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
 * Class SitemapXMLGenerator
 * Generate the Sitemap XML based on one or several WebspaceSitemaps.
 */
interface SitemapXMLGeneratorInterface
{
    /**
     * Returns the generate Sitemap XML.
     *
     * @param WebspaceSitemap[] $webspaceSitemaps
     * @param string $domain
     * @param string $scheme
     * @param string $renderFile
     *
     * @return string
     */
    public function generate($webspaceSitemaps, $domain = null, $scheme = 'http', $renderFile = null);
}
