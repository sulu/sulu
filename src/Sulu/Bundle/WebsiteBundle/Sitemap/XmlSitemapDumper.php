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
     */
    public function __construct(
        XmlSitemapRendererInterface $sitemapRenderer,
        SitemapProviderPoolInterface $sitemapProviderPool,
        Filesystem $filesystem,
        $baseDirectory
    ) {
        $this->sitemapRenderer = $sitemapRenderer;
        $this->sitemapProviderPool = $sitemapProviderPool;
        $this->filesystem = $filesystem;
        $this->baseDirectory = $baseDirectory;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexDumpPath($scheme, $host)
    {
        return sprintf(
            '%s/%s/%s/sitemap.xml',
            rtrim($this->baseDirectory, '/'),
            $scheme,
            $host
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDumpPath($scheme, $host, $alias, $page)
    {
        return sprintf(
            '%s/%s/%s/sitemaps/%s-%s.xml',
            rtrim($this->baseDirectory, '/'),
            $scheme,
            $host,
            $alias,
            $page
        );
    }

    /**
     * {@inheritdoc}
     */
    public function dumpHost($scheme, $host)
    {
        $dumpPath = $this->getIndexDumpPath($scheme, $host);
        $sitemap = $this->sitemapRenderer->renderIndex($scheme, $host);
        if (!$sitemap) {
            $aliases = array_keys($this->sitemapProviderPool->getProviders());
            $this->dumpFile(
                $dumpPath,
                $this->sitemapRenderer->renderSitemap(
                    reset($aliases),
                    1,
                    $scheme,
                    $host
                )
            );

            return;
        }

        foreach ($this->sitemapProviderPool->getProviders() as $alias => $provider) {
            $this->dumpProviderSitemap($alias, $scheme, $host);
        }

        $this->dumpFile($dumpPath, $sitemap);
    }

    /**
     * Render sitemap for provider.
     *
     * @param string $alias
     * @param string $scheme
     * @param string $host
     */
    private function dumpProviderSitemap($alias, $scheme, $host)
    {
        $maxPage = $this->sitemapProviderPool->getProvider($alias)->getMaxPage($scheme, $host);
        for ($page = 1; $page <= $maxPage; ++$page) {
            $sitemap = $this->sitemapRenderer->renderSitemap(
                $alias,
                $page,
                $scheme,
                $host
            );

            $this->dumpFile(
                $this->getDumpPath(
                    $scheme,
                    $host,
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
