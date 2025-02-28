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

/**
 * Pool of all sitemap-providers.
 */
class SitemapProviderPool implements SitemapProviderPoolInterface
{
    /**
     * @var SitemapProviderInterface[]
     */
    private $providers;

    /**
     * @var Sitemap[]
     */
    private $index;

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

        if (isset($this->index[$key])) {
            return $this->index[$key];
        }

        $indexes = [];
        foreach ($this->providers as $alias => $provider) {
            $indexes[] = $provider->createSitemap($scheme, $host);
        }

        $this->index[$key] = $indexes;

        return $this->index[$key];
    }
}
