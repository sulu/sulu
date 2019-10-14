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

use Twig\Environment;

/**
 * Render sitemap in xml-format.
 */
class XmlSitemapRenderer implements XmlSitemapRendererInterface
{
    /**
     * @var SitemapProviderPoolInterface
     */
    private $sitemapProviderPool;

    /**
     * @var Environment
     */
    private $engine;

    /**
     * @param SitemapProviderPoolInterface $sitemapProviderPool
     * @param Environment $engine
     */
    public function __construct(
        SitemapProviderPoolInterface $sitemapProviderPool,
        Environment $engine
    ) {
        $this->sitemapProviderPool = $sitemapProviderPool;
        $this->engine = $engine;
    }

    /**
     * {@inheritdoc}
     */
    public function renderIndex($scheme, $host)
    {
        if (!$this->needsIndex($scheme, $host)) {
            return null;
        }

        return $this->render(
            'SuluWebsiteBundle:Sitemap:sitemap-index.xml.twig',
            ['sitemaps' => $this->sitemapProviderPool->getIndex($scheme, $host), 'domain' => $host, 'scheme' => $scheme]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderSitemap($alias, $page, $scheme, $host)
    {
        if (!$this->sitemapProviderPool->hasProvider($alias)) {
            return null;
        }

        $provider = $this->sitemapProviderPool->getProvider($alias);
        if ($provider->getMaxPage($scheme, $host) < $page) {
            return null;
        }

        $entries = $provider->build($page, $scheme, $host);

        return $this->render(
            'SuluWebsiteBundle:Sitemap:sitemap.xml.twig',
            [
                'domain' => $host,
                'scheme' => $scheme,
                'entries' => $entries,
            ]
        );
    }

    /**
     * Renders a view.
     *
     * @param string $view The view name
     * @param array $parameters An array of parameters to pass to the view
     *
     * @return string
     */
    private function render($view, array $parameters = [])
    {
        return $this->engine->render($view, $parameters);
    }

    /**
     * Returns true if a index exists.
     *
     * @return bool
     */
    private function needsIndex($scheme, $host)
    {
        return 1 < count($this->sitemapProviderPool->getProviders())
        || 1 < array_reduce(
            $this->sitemapProviderPool->getIndex($scheme, $host),
            function($v1, Sitemap $v2) use ($scheme, $host) {
                return $v1 + $v2->getMaxPage($scheme, $host);
            }
        );
    }
}
