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

/**
 * Interface for sitemap-provider pool.
 */
interface SitemapProviderPoolInterface
{
    /**
     * Returns provider for given alias.
     *
     * @param string $alias
     *
     * @return SitemapProviderInterface
     */
    public function getProvider($alias);

    /**
     * Returns all providers.
     *
     * @return SitemapProviderInterface[]
     */
    public function getProviders();

    /**
     * Indicates that the provider with alias exists.
     *
     * @param string $alias
     *
     * @return bool
     */
    public function hasProvider($alias);

    /**
     * Returns list of available sitemaps.
     *
     * @return Sitemap[]
     */
    public function getIndex();
}
