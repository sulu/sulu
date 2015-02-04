<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Bundle\WebsiteBundle\Sitemap;


/**
 * Interface for Sitemap Dumper
 */
interface SitemapDumperInterface
{
    /**
     * @param $sitemapPages
     * @param string $defaultLocale
     * @param string $webspaceKey
     * @param string $portalKey
     * @param bool $dumpFile
     * @param string $format
     * @return string
     */
    public function dump($sitemapPages, $defaultLocale, $webspaceKey, $portalKey, $dumpFile = false, $format = 'xml');

    /**
     * @param $webspaceKey
     * @param $portalKey
     * @return string
     */
    public function get($webspaceKey, $portalKey);

    /**
     * @param $webspaceKey
     * @param $portalKey
     * @return bool
     */
    public function getPath($webspaceKey, $portalKey);
}
