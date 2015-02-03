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

use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Templating\EngineInterface;

/**
 * Class SitemapDumper
 */
class SitemapDumper
{
    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var string
     */
    private $siteMapCachePath;

    /**
     * @param EngineInterface $templating
     * @param $siteMapCachePath
     */
    public function __construct(
        EngineInterface $templating,
        $siteMapCachePath
    )
    {
        $this->templating = $templating;
        $this->siteMapCachePath = $siteMapCachePath;
    }

    /**
     * @param $sitemapPages
     * @param $defaultLocale
     * @param $webspaceKey
     * @param $portalKey
     * @param bool $dumpFile
     * @return string
     */
    public function dump($sitemapPages, $defaultLocale, $webspaceKey, $portalKey, $dumpFile = false, $format = 'xml')
    {
        $sitemapXml = $this->templating->render(
            'SuluWebsiteBundle:Sitemap:sitemap.'.$format.'.twig',
            array(
                'sitemap' => $sitemapPages,
                'defaultLocale' => $defaultLocale,
            )
        );

        if ($dumpFile) {
            $filesystem = new Filesystem();
            $filesystem->dumpFile($this->getSitemapPath($webspaceKey, $portalKey), $sitemapXml);
        }

        return $sitemapXml;
    }

    /**
     * @param $webspaceKey
     * @param $portalKey
     * @return bool
     */
    public function sitemapExists($webspaceKey, $portalKey)
    {
        if (file_exists($this->getSitemapPath($webspaceKey, $portalKey))) {
            return true;
        }
        return false;
    }

    /**
     * @param $webspaceKey
     * @param $portalKey
     * @return string
     */
    private function getSitemapPath($webspaceKey, $portalKey)
    {
        return sprintf('%s/%s.xml', $this->siteMapCachePath, $webspaceKey . '_' . $portalKey);
    }
}
