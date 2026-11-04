<?php

declare(strict_types=1);

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
 * Collects the sitemap-urls of the registered sitemap-providers as a single flat list.
 *
 * The XML sitemap is split into one file per provider and page. A human readable sitemap
 * instead wants to render all urls of a host at once. Because the urls are read from the
 * same providers which are used to render the XML sitemap, the collected urls contain
 * pages, articles and every other custom routable entity which registers a sitemap-provider.
 */
interface SitemapUrlCollectorInterface
{
    /**
     * @param string $scheme scheme of the current request e.g. "https"
     * @param string $host host of the current request e.g. "sulu.io"
     * @param string|null $locale locale to filter the urls by, "null" returns the urls of all locales of the host
     * @param string|null $alias alias of a single sitemap-provider, "null" collects the urls of all providers
     * @param int $page page of the sitemap-provider, see SitemapProviderInterface::PAGE_SIZE
     *
     * @return SitemapUrl[]
     *
     * @throws SitemapProviderNotFoundException if the given alias does not exist
     */
    public function collect(
        string $scheme,
        string $host,
        ?string $locale = null,
        ?string $alias = null,
        int $page = 1
    ): array;
}
