<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Manager;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Util\WildcardUrlUtil;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\Dumper\PhpWebspaceCollectionDumper;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * This class is responsible for loading, reading and caching the portal configuration files.
 */
class WebspaceManager implements WebspaceManagerInterface
{
    /**
     * @var WebspaceCollection
     */
    private $webspaceCollection;

    /**
     * @var array
     */
    private $options;

    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var ReplacerInterface
     */
    private $urlReplacer;

    /**
     * @var string
     */
    private $environment;

    public function __construct(
        LoaderInterface $loader,
        ReplacerInterface $urlReplacer,
        array $options,
        string $environment
    ) {
        $this->loader = $loader;
        $this->urlReplacer = $urlReplacer;
        $this->setOptions($options);
        $this->environment = $environment;
    }

    public function findWebspaceByKey(?string $key): ?Webspace
    {
        if (!$key) {
            return null;
        }

        return $this->getWebspaceCollection()->getWebspace($key);
    }

    public function findPortalByKey(?string $key): ?Portal
    {
        if (!$key) {
            return null;
        }

        return $this->getWebspaceCollection()->getPortal($key);
    }

    public function findPortalInformationByUrl(string $url, ?string $environment = null): ?PortalInformation
    {
        if (null === $environment) {
            $environment = $this->environment;
        }

        $portalInformations = $this->getWebspaceCollection()->getPortalInformations($environment);
        foreach ($portalInformations as $portalInformation) {
            if ($this->matchUrl($url, $portalInformation->getUrl())) {
                return $portalInformation;
            }
        }

        return null;
    }

    public function findPortalInformationsByHostIncludingSubdomains(string $host, ?string $environment = null): array
    {
        if (null === $environment) {
            $environment = $this->environment;
        }

        return array_filter(
            $this->getWebspaceCollection()->getPortalInformations($environment),
            function(PortalInformation $portalInformation) use ($host) {
                $portalHost = $portalInformation->getHost();

                if ($this->urlReplacer->hasHostReplacer($portalHost)) {
                    $portalHost = $this->urlReplacer->replaceHost($portalHost, $host);
                }

                // add a slash to avoid problems with "example.co" and "example.com"
                return false !== strpos($portalHost . '/', $host . '/');
            }
        );
    }

    public function findPortalInformationsByUrl(string $url, ?string $environment = null): array
    {
        if (null === $environment) {
            $environment = $this->environment;
        }

        return array_filter(
            $this->getWebspaceCollection()->getPortalInformations($environment),
            function(PortalInformation $portalInformation) use ($url) {
                return $this->matchUrl($url, $portalInformation->getUrl());
            }
        );
    }

    public function findPortalInformationsByWebspaceKeyAndLocale(
        string $webspaceKey,
        string $locale,
        ?string $environment = null
    ): array {
        if (null === $environment) {
            $environment = $this->environment;
        }

        return array_filter(
            $this->getWebspaceCollection()->getPortalInformations($environment),
            function(PortalInformation $portalInformation) use ($webspaceKey, $locale) {
                return $portalInformation->getWebspace()->getKey() === $webspaceKey
                    && $portalInformation->getLocale() === $locale;
            }
        );
    }

    public function findPortalInformationsByPortalKeyAndLocale(
        string $portalKey,
        string $locale,
        ?string $environment = null
    ): array {
        if (null === $environment) {
            $environment = $this->environment;
        }

        return array_filter(
            $this->getWebspaceCollection()->getPortalInformations($environment),
            function(PortalInformation $portalInformation) use ($portalKey, $locale) {
                return $portalInformation->getPortal()
                    && $portalInformation->getPortal()->getKey() === $portalKey
                    && $portalInformation->getLocale() === $locale;
            }
        );
    }

    public function findUrlsByResourceLocator(
        string $resourceLocator,
        ?string $environment,
        string $languageCode,
        ?string $webspaceKey = null,
        ?string $domain = null,
        string $scheme = 'http'
    ): array {
        if (null === $environment) {
            $environment = $this->environment;
        }

        $urls = [];
        $portals = $this->getWebspaceCollection()->getPortalInformations(
            $environment,
            [RequestAnalyzerInterface::MATCH_TYPE_FULL]
        );
        foreach ($portals as $portalInformation) {
            $sameLocalization = $portalInformation->getLocalization()->getLocale() === $languageCode;
            $sameWebspace = null === $webspaceKey || $portalInformation->getWebspace()->getKey() === $webspaceKey;
            $url = $this->createResourceLocatorUrl($scheme, $portalInformation->getUrl(), $resourceLocator, $domain);
            if ($sameLocalization && $sameWebspace && $this->isFromDomain($url, $domain)) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    public function findUrlByResourceLocator(
        ?string $resourceLocator,
        ?string $environment,
        string $languageCode,
        ?string $webspaceKey = null,
        ?string $domain = null,
        string $scheme = 'http'
    ): ?string {
        if (null === $environment) {
            $environment = $this->environment;
        }

        $urls = [];
        $portals = $this->getWebspaceCollection()->getPortalInformations(
            $environment,
            [
                RequestAnalyzerInterface::MATCH_TYPE_FULL,
                RequestAnalyzerInterface::MATCH_TYPE_PARTIAL,
                RequestAnalyzerInterface::MATCH_TYPE_REDIRECT,
            ]
        );

        foreach ($portals as $portalInformation) {
            $sameLocalization = (
                null === $portalInformation->getLocalization()
                || $portalInformation->getLocalization()->getLocale() === $languageCode
            );
            $sameWebspace = null === $webspaceKey || $portalInformation->getWebspace()->getKey() === $webspaceKey;
            $url = $this->createResourceLocatorUrl($scheme, $portalInformation->getUrl(), $resourceLocator, $domain);
            if ($sameLocalization && $sameWebspace && $this->isFromDomain($url, $domain)) {
                if ($portalInformation->isMain()) {
                    array_unshift($urls, $url);
                } else {
                    $urls[] = $url;
                }
            }
        }

        return reset($urls) ?: null;
    }

    public function getPortals(): array
    {
        return $this->getWebspaceCollection()->getPortals();
    }

    public function getUrls(?string $environment = null): array
    {
        if (null === $environment) {
            $environment = $this->environment;
        }

        $urls = [];

        foreach ($this->getWebspaceCollection()->getPortalInformations($environment) as $portalInformation) {
            $urls[] = $portalInformation->getUrl();
        }

        return $urls;
    }

    /**
     * {@inheritdoc}
     */
    public function getPortalInformations(?string $environment = null): array
    {
        if (null === $environment) {
            $environment = $this->environment;
        }

        return $this->getWebspaceCollection()->getPortalInformations($environment);
    }

    public function getPortalInformationsByWebspaceKey(?string $environment = null, string $webspaceKey): array
    {
        if (null === $environment) {
            $environment = $this->environment;
        }

        return array_filter(
            $this->getWebspaceCollection()->getPortalInformations($environment),
            function(PortalInformation $portal) use ($webspaceKey) {
                return $portal->getWebspaceKey() === $webspaceKey;
            }
        );
    }

    public function getAllLocalizations(): array
    {
        $localizations = [];

        foreach ($this->getWebspaceCollection() as $webspace) {
            /** @var Webspace $webspace */
            foreach ($webspace->getAllLocalizations() as $localization) {
                $localizations[$localization->getLocale()] = $localization;
            }
        }

        return $localizations;
    }

    public function getAllLocales(): array
    {
        return array_values(
            array_map(
                function(Localization $localization) {
                    return $localization->getLocale();
                },
                $this->getAllLocalizations()
            )
        );
    }

    public function getAllLocalesByWebspaces(): array
    {
        $webspaces = [];
        foreach ($this->getWebspaceCollection() as $webspace) {
            /** @var Webspace $webspace */
            $locales = [];
            $defaultLocale = $webspace->getDefaultLocalization();
            $locales[$defaultLocale->getLocale()] = $defaultLocale;
            foreach ($webspace->getAllLocalizations() as $localization) {
                if (!array_key_exists($localization->getLocale(), $locales)) {
                    $locales[$localization->getLocale()] = $localization;
                }
            }
            $webspaces[$webspace->getKey()] = $locales;
        }

        return $webspaces;
    }

    public function getWebspaceCollection(): WebspaceCollection
    {
        if (null === $this->webspaceCollection) {
            $class = $this->options['cache_class'];
            $cache = new ConfigCache(
                $this->options['cache_dir'] . '/' . $class . '.php',
                $this->options['debug']
            );

            if (!$cache->isFresh()) {
                $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
                    $this->loader,
                    $this->urlReplacer,
                    $this->options['config_dir']
                );
                $webspaceCollection = $webspaceCollectionBuilder->build();
                $dumper = new PhpWebspaceCollectionDumper($webspaceCollection);
                $cache->write(
                    $dumper->dump(
                        [
                            'cache_class' => $class,
                            'base_class' => $this->options['base_class'],
                        ]
                    ),
                    $webspaceCollection->getResources()
                );
            }

            require_once $cache->getPath();

            $this->webspaceCollection = new $class();
        }

        return $this->webspaceCollection;
    }

    /**
     * Sets the options for the manager.
     *
     * @param mixed[] $options
     */
    public function setOptions($options)
    {
        $this->options = [
            'config_dir' => null,
            'cache_dir' => null,
            'debug' => false,
            'cache_class' => 'WebspaceCollectionCache',
            'base_class' => 'WebspaceCollection',
        ];

        // overwrite the default values with the given options
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Url is from domain.
     *
     * @param string $url
     * @param string $domain
     *
     * @return bool
     */
    protected function isFromDomain($url, $domain)
    {
        if (!$domain) {
            return true;
        }

        $parsedUrl = parse_url($url);
        // if domain or subdomain
        if (
            isset($parsedUrl['host'])
            && (
                $parsedUrl['host'] == $domain
                || fnmatch('*.' . $domain, $parsedUrl['host'])
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Matches given url with portal-url.
     *
     * @param string $url
     * @param string $portalUrl
     *
     * @return bool
     */
    protected function matchUrl($url, $portalUrl)
    {
        return WildcardUrlUtil::match($url, $portalUrl);
    }

    /**
     * Return a valid resource locator url.
     *
     * @param string $scheme
     * @param string $portalUrl
     * @param string $resourceLocator
     * @param string|null $domain
     *
     * @return string
     */
    private function createResourceLocatorUrl($scheme, $portalUrl, $resourceLocator, $domain = null)
    {
        if (false !== strpos($portalUrl, '/')) {
            // trim slash when resourceLocator is not domain root
            $resourceLocator = rtrim($resourceLocator, '/');
        }

        if ($domain && $this->urlReplacer->hasHostReplacer($portalUrl)) {
            $portalUrl = $this->urlReplacer->replaceHost($portalUrl, $domain);
        }

        return rtrim(sprintf('%s://%s', $scheme, $portalUrl), '/') . $resourceLocator;
    }
}
