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

use Sulu\Bundle\WebsiteBundle\Exception\SitemapProviderNotFoundException;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Pool of all sitemap-providers.
 */
class SitemapProviderPool implements SitemapProviderPoolInterface, ResetInterface
{
    /**
     * @var SitemapProviderInterface[]
     */
    private $providers;

    /**
     * @var array<string, Sitemap[]>
     */
    private $sitemapsPerHost;

    /**
     * @param SitemapProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->getAlias()] = $provider;
        }
    }

    public function getProvider($alias)
    {
        if (!$this->hasProvider($alias)) {
            throw new SitemapProviderNotFoundException($alias, \array_keys($this->providers));
        }

        return $this->providers[$alias];
    }

    public function getProviders()
    {
        return $this->providers;
    }

    public function hasProvider($alias)
    {
        return \array_key_exists($alias, $this->providers);
    }

    public function getIndex($scheme, $host)
    {
        $key = $scheme . $host;

        if (isset($this->sitemapsPerHost[$key])) {
            return $this->sitemapsPerHost[$key];
        }

        $sitemapsPerHost = [];
        foreach ($this->providers as $alias => $provider) {
            $sitemapsPerHost[] = $provider->createSitemap($scheme, $host);
        }

        $this->sitemapsPerHost[$key] = $sitemapsPerHost;

        return $this->sitemapsPerHost[$key];
    }

    /**
     * @internal this method is for internal use only and should not be used by other classes
     */
    public function reset(): void
    {
        $this->sitemapsPerHost = [];
    }
}
