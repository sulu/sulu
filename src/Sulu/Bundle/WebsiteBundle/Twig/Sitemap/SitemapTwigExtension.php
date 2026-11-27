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

namespace Sulu\Bundle\WebsiteBundle\Twig\Sitemap;

use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderPoolInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrlCollectorInterface;
use Sulu\Component\Cache\MemoizeInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides twig functions to render a human readable sitemap.
 *
 * @final
 *
 * @internal This class is internal and should not be extended or overwritten.
 *           You can create an own Twig Extension to override the behaviour.
 */
class SitemapTwigExtension extends AbstractExtension
{
    public function __construct(
        private SitemapUrlCollectorInterface $sitemapUrlCollector,
        private SitemapProviderPoolInterface $sitemapProviderPool,
        private WebspaceManagerInterface $webspaceManager,
        private RequestStack $requestStack,
        private MemoizeInterface $memoizeCache,
        private string $environment,
        private int $cacheLifetime,
        private ?RequestAnalyzerInterface $requestAnalyzer = null,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('sulu_sitemap', [$this, 'sitemapFunction']),
            new TwigFunction('sulu_sitemap_url', [$this, 'sitemapUrlFunction']),
            new TwigFunction('sulu_sitemap_aliases', [$this, 'sitemapAliasesFunction']),
        ];
    }

    /**
     * Returns the sitemap-urls of the current host, which contains the urls of all registered
     * sitemap-providers: pages, articles and every other custom routable entity.
     *
     * @param string|null $locale locale to filter the urls by, defaults to the locale of the current request
     * @param string|null $alias alias of a single sitemap-provider e.g. "pages", "null" returns all providers
     * @param int $page page of the sitemap-provider, see SitemapProviderInterface::PAGE_SIZE
     *
     * @return SitemapUrl[]
     */
    public function sitemapFunction(?string $locale = null, ?string $alias = null, int $page = 1): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return [];
        }

        $locale ??= $this->requestAnalyzer?->getCurrentLocalization()?->getLocale();

        /** @var SitemapUrl[] $sitemapUrls */
        $sitemapUrls = $this->memoizeCache->memoizeById(
            'sulu_sitemap',
            [$request->getScheme(), $request->getHost(), $locale, $alias, $page],
            fn (string $scheme, string $host, ?string $locale, ?string $alias, int $page): array => $this->sitemapUrlCollector->collect($scheme, $host, $locale, $alias, $page),
            $this->cacheLifetime
        );

        return $sitemapUrls;
    }

    /**
     * Returns the absolute url of the given slug, defaults to the webspace and locale of the current request.
     */
    public function sitemapUrlFunction(?string $slug, ?string $locale = null, ?string $webspaceKey = null): ?string
    {
        $webspaceKey ??= $this->requestAnalyzer?->getWebspace()?->getKey();
        $locale ??= $this->requestAnalyzer?->getCurrentLocalization()?->getLocale();

        if (null === $locale) {
            return null;
        }

        return $this->webspaceManager->findUrlByResourceLocator(
            $slug,
            $this->environment,
            $locale,
            $webspaceKey
        );
    }

    /**
     * Returns the aliases of all registered sitemap-providers e.g. ["pages", "articles"].
     *
     * @return string[]
     */
    public function sitemapAliasesFunction(): array
    {
        return \array_values(\array_map(
            static fn (SitemapProviderInterface $provider): string => $provider->getAlias(),
            $this->sitemapProviderPool->getProviders()
        ));
    }
}
