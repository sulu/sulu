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

use Sulu\Component\Webspace\PortalInformation;

/**
 * Interface for sitemap-dumper.
 */
interface XmlSitemapDumperInterface
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
     * Dump sitemaps for given portal-information.
     *
     * @param PortalInformation $portalInformation
     * @param string $scheme
     */
    public function dumpPortalInformation(PortalInformation $portalInformation, $scheme);
}
