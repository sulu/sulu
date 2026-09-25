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

/**
 * @internal your code should not create direct dependencies on this implementation,
 *           use the SitemapUrlCollectorInterface instead
 */
class SitemapUrlCollector implements SitemapUrlCollectorInterface
{
    public function __construct(
        private SitemapProviderPoolInterface $sitemapProviderPool,
    ) {
    }

    public function collect(
        string $scheme,
        string $host,
        ?string $locale = null,
        ?string $alias = null,
        int $page = 1
    ): array {
        $providers = null !== $alias
            ? [$alias => $this->sitemapProviderPool->getProvider($alias)]
            : $this->sitemapProviderPool->getProviders();

        $sitemapUrls = [];

        foreach ($providers as $provider) {
            if ($provider->getMaxPage($scheme, $host) < $page) {
                continue;
            }

            foreach ($provider->build($page, $scheme, $host) as $sitemapUrl) {
                if (null !== $locale && $sitemapUrl->getLocale() !== $locale) {
                    continue;
                }

                // providers can return urls of other hosts, the xml sitemap template filters them the same way
                if (!\str_contains($sitemapUrl->getLoc(), $host)) {
                    continue;
                }

                $sitemapUrls[] = $sitemapUrl;
            }
        }

        return $sitemapUrls;
    }
}
