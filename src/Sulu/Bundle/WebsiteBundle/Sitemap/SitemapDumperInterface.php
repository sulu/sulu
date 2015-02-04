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
 * Class SitemapDumper
 */
interface SitemapDumperInterface
{
    /**
     * @param $sitemapPages
     * @param $defaultLocale
     * @param $webspaceKey
     * @param $portalKey
     * @param bool $dumpFile
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
    public function sitemapExists($webspaceKey, $portalKey);
}
