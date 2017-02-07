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

use Sulu\Component\Webspace\Portal;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

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
     * @var EngineInterface
     */
    private $engine;

    /**
     * @param SitemapProviderPoolInterface $sitemapProviderPool
     * @param EngineInterface $engine
     */
    public function __construct(
        SitemapProviderPoolInterface $sitemapProviderPool,
        EngineInterface $engine
    ) {
        $this->sitemapProviderPool = $sitemapProviderPool;
        $this->engine = $engine;
    }

    /**
     * {@inheritdoc}
     */
    public function renderIndex($domain = null, $scheme = null)
    {
        if (!$this->needsIndex()) {
            return;
        }

        return $this->render(
            'SuluWebsiteBundle:Sitemap:sitemap-index.xml.twig',
            ['sitemaps' => $this->sitemapProviderPool->getIndex(), 'domain' => $domain, 'scheme' => $scheme]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderSitemap($alias, $page, $locale, Portal $portal, $host, $scheme)
    {
        if (!$this->sitemapProviderPool->hasProvider($alias)) {
            return;
        }

        $provider = $this->sitemapProviderPool->getProvider($alias);
        if ($provider->getMaxPage() < $page) {
            return;
        }

        $entries = $provider->build($page, $portal->getKey(), $locale);

        return $this->render(
            'SuluWebsiteBundle:Sitemap:sitemap.xml.twig',
            [
                'webspaceKey' => $portal->getWebspace()->getKey(),
                'locale' => $locale,
                'defaultLocale' => $portal->getXDefaultLocalization()->getLocale(),
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
    private function needsIndex()
    {
        return 1 < count($this->sitemapProviderPool->getProviders())
        || 1 < array_reduce(
            $this->sitemapProviderPool->getIndex(),
            function ($v1, Sitemap $v2) {
                return $v1 + $v2->getMaxPage();
            }
        );
    }
}
