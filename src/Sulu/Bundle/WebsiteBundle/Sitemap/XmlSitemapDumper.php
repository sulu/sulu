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
use Symfony\Component\Filesystem\Filesystem;

/**
 * This class provides functionality to dump sitemaps.
 */
class XmlSitemapDumper implements XmlSitemapDumperInterface
{
    /**
     * @var string
     */
    private $baseDirectory;

    /**
     * @var string
     */
    private $defaultHost;

    /**
     * @var XmlSitemapRendererInterface
     */
    private $sitemapRenderer;

    /**
     * @var SitemapProviderPoolInterface
     */
    private $sitemapProviderPool;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param XmlSitemapRendererInterface $sitemapRenderer
     * @param SitemapProviderPoolInterface $sitemapProviderPool
     * @param Filesystem $filesystem
     * @param string $baseDirectory
     * @param string $defaultHost
     */
    public function __construct(
        XmlSitemapRendererInterface $sitemapRenderer,
        SitemapProviderPoolInterface $sitemapProviderPool,
        Filesystem $filesystem,
        $baseDirectory,
        $defaultHost
    ) {
        $this->sitemapRenderer = $sitemapRenderer;
        $this->sitemapProviderPool = $sitemapProviderPool;
        $this->filesystem = $filesystem;
        $this->baseDirectory = $baseDirectory;
        $this->defaultHost = $defaultHost;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexDumpPath($scheme, $webspaceKey, $locale, $url)
    {
        return sprintf(
            '%s/%s/%s/%s/%s/sitemap.xml',
            rtrim($this->baseDirectory, '/'),
            $scheme,
            $webspaceKey,
            $locale,
            $url
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDumpPath($scheme, $webspaceKey, $locale, $url, $alias, $page)
    {
        return sprintf(
            '%s/%s/%s/%s/%s/sitemaps/%s-%s.xml',
            rtrim($this->baseDirectory, '/'),
            $scheme,
            $webspaceKey,
            $locale,
            $url,
            $alias,
            $page
        );
    }

    /**
     * {@inheritdoc}
     */
    public function dumpPortalInformation(PortalInformation $portalInformation, $scheme)
    {
        if (false !== strpos($portalInformation->getUrl(), '{host}')) {
            if (!$this->defaultHost) {
                return;
            }

            $portalInformation->setUrl(str_replace('{host}', $this->defaultHost, $portalInformation->getUrl()));
        }

        $dumpPath = $this->getIndexDumpPath(
            $scheme,
            $portalInformation->getWebspaceKey(),
            $portalInformation->getLocale(),
            $portalInformation->getHost()
        );
        $sitemap = $this->sitemapRenderer->renderIndex($portalInformation->getHost(), $scheme);
        if (!$sitemap) {
            $aliases = array_keys($this->sitemapProviderPool->getProviders());
            $this->dumpFile(
                $dumpPath,
                $this->sitemapRenderer->renderSitemap(
                    reset($aliases),
                    1,
                    $portalInformation->getLocale(),
                    $portalInformation->getPortal(),
                    $portalInformation->getHost(),
                    $scheme
                )
            );

            return;
        }

        foreach ($this->sitemapProviderPool->getProviders() as $alias => $provider) {
            $this->dumpProviderSitemap($alias, $portalInformation, $scheme);
        }

        $this->dumpFile($dumpPath, $sitemap);
    }

    /**
     * Render sitemap for provider.
     *
     * @param string $alias
     * @param PortalInformation $portalInformation
     * @param string $scheme
     */
    private function dumpProviderSitemap($alias, PortalInformation $portalInformation, $scheme)
    {
        $maxPage = $this->sitemapProviderPool->getProvider($alias)->getMaxPage();
        for ($page = 1; $page <= $maxPage; ++$page) {
            $sitemap = $this->sitemapRenderer->renderSitemap(
                $alias,
                $page,
                $portalInformation->getLocale(),
                $portalInformation->getPortal(),
                $portalInformation->getHost(),
                $scheme
            );

            $this->dumpFile(
                $this->getDumpPath(
                    $scheme,
                    $portalInformation->getWebspaceKey(),
                    $portalInformation->getLocale(),
                    $portalInformation->getHost(),
                    $alias,
                    $page
                ),
                $sitemap
            );
        }
    }

    /**
     * Dump content into given filename.
     *
     * @param string $filePath
     * @param string $content
     */
    private function dumpFile($filePath, $content)
    {
        $this->filesystem->dumpFile($filePath, $content);
    }
}
