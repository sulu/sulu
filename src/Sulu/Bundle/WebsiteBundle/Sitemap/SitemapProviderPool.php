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
     * @var string[]
     */
    private $aliases;

    /**
     * @var Sitemap[]
     */
    private $index;

    /**
     * @param SitemapProviderInterface[] $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
        $this->aliases = array_keys($providers);
    }

    /**
     * {@inheritdoc}
     */
    public function getProvider($alias)
    {
        if (!$this->hasProvider($alias)) {
            throw new SitemapProviderNotFoundException($alias, $this->aliases);
        }

        return $this->providers[$alias];
    }

    /**
     * {@inheritdoc}
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * {@inheritdoc}
     */
    public function hasProvider($alias)
    {
        return array_key_exists($alias, $this->providers);
    }

    /**
     * {@inheritdoc}
     */
    public function getIndex()
    {
        if ($this->index) {
            return $this->index;
        }

        $this->index = [];
        foreach ($this->providers as $alias => $provider) {
            $this->index[] = $provider->createSitemap($alias);
        }

        return $this->index;
    }
}
