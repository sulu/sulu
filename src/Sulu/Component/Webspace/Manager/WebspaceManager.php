<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Manager;

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

    public function __construct(
        LoaderInterface $loader,
        ReplacerInterface $urlReplacer,
        $options = []
    ) {
        $this->loader = $loader;
        $this->urlReplacer = $urlReplacer;
        $this->setOptions($options);
    }

    /**
     * Returns the webspace with the given key.
     *
     * @param $key string The key to search for
     *
     * @return Webspace
     */
    public function findWebspaceByKey($key)
    {
        return $this->getWebspaceCollection()->getWebspace($key);
    }

    /**
     * Returns the portal with the given key.
     *
     * @param string $key The key to search for
     *
     * @return Portal
     */
    public function findPortalByKey($key)
    {
        return $this->getWebspaceCollection()->getPortal($key);
    }

    /**
     * {@inheritdoc}
     */
    public function findPortalInformationByUrl($url, $environment)
    {
        $portalInformations = $this->getWebspaceCollection()->getPortalInformations($environment);
        foreach ($portalInformations as $portalInformation) {
            if ($this->matchUrl($url, $portalInformation->getUrl())) {
                return $portalInformation;
            }
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function findPortalInformationsByUrl($url, $environment)
    {
        return array_filter(
            $this->getWebspaceCollection()->getPortalInformations($environment),
            function (PortalInformation $portalInformation) use ($url) {
                return $this->matchUrl($url, $portalInformation->getUrl());
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findPortalInformationsByWebspaceKeyAndLocale($webspaceKey, $locale, $environment)
    {
        return array_filter(
            $this->getWebspaceCollection()->getPortalInformations($environment),
            function (PortalInformation $portalInformation) use ($webspaceKey, $locale) {
                return $portalInformation->getWebspace()->getKey() === $webspaceKey
                    && $portalInformation->getLocale() === $locale;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findPortalInformationsByPortalKeyAndLocale($portalKey, $locale, $environment)
    {
        return array_filter(
            $this->getWebspaceCollection()->getPortalInformations($environment),
            function (PortalInformation $portalInformation) use ($portalKey, $locale) {
                return $portalInformation->getPortal()
                    && $portalInformation->getPortal()->getKey() === $portalKey
                    && $portalInformation->getLocale() === $locale;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findUrlsByResourceLocator(
        $resourceLocator,
        $environment,
        $languageCode,
        $webspaceKey = null,
        $domain = null,
        $scheme = 'http'
    ) {
        $urls = [];
        $portals = $this->getWebspaceCollection()->getPortalInformations(
            $environment,
            [RequestAnalyzerInterface::MATCH_TYPE_FULL]
        );
        foreach ($portals as $portalInformation) {
            $sameLocalization = $portalInformation->getLocalization()->getLocalization() === $languageCode;
            $sameWebspace = $webspaceKey === null || $portalInformation->getWebspace()->getKey() === $webspaceKey;
            $url = rtrim(sprintf('%s://%s%s', $scheme, $portalInformation->getUrl(), $resourceLocator), '/');
            if ($sameLocalization && $sameWebspace && $this->isFromDomain($url, $domain)) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /**
     * {@inheritdoc}
     */
    public function findUrlByResourceLocator(
        $resourceLocator,
        $environment,
        $languageCode,
        $webspaceKey = null,
        $domain = null,
        $scheme = 'http'
    ) {
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
                $portalInformation->getLocalization() === null
                || $portalInformation->getLocalization()->getLocalization() === $languageCode
            );
            $sameWebspace = $webspaceKey === null || $portalInformation->getWebspace()->getKey() === $webspaceKey;
            $url = rtrim(sprintf('%s://%s%s', $scheme, $portalInformation->getUrl(), $resourceLocator), '/');
            if ($sameLocalization && $sameWebspace && $this->isFromDomain($url, $domain)) {
                if ($portalInformation->isMain()) {
                    array_unshift($urls, $url);
                } else {
                    $urls[] = $url;
                }
            }
        }

        return reset($urls);
    }

    /**
     * {@inheritdoc}
     */
    public function getPortals()
    {
        return $this->getWebspaceCollection()->getPortals();
    }

    /**
     * {@inheritdoc}
     */
    public function getUrls($environment)
    {
        $urls = [];

        foreach ($this->getWebspaceCollection()->getPortalInformations($environment) as $portalInformation) {
            $urls[] = $portalInformation->getUrl();
        }

        return $urls;
    }

    /**
     * {@inheritdoc}
     */
    public function getPortalInformations($environment)
    {
        return $this->getWebspaceCollection()->getPortalInformations($environment);
    }

    /**
     * {@inheritdoc}
     */
    public function getPortalInformationsByWebspaceKey($environment, $webspaceKey)
    {
        return array_filter(
            $this->getWebspaceCollection()->getPortalInformations($environment),
            function (PortalInformation $portal) use ($webspaceKey) {
                return $portal->getWebspaceKey() === $webspaceKey;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAllLocalizations()
    {
        $localizations = [];

        foreach ($this->getWebspaceCollection() as $webspace) {
            /** @var Webspace $webspace */
            foreach ($webspace->getAllLocalizations() as $localization) {
                $localizations[$localization->getLocalization()] = $localization;
            }
        }

        return $localizations;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllLocalesByWebspaces()
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

    /**
     * Returns all the webspaces managed by this specific instance.
     *
     * @return WebspaceCollection
     */
    public function getWebspaceCollection()
    {
        if ($this->webspaceCollection === null) {
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
     * @param $options
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
     * @param $url
     * @param $domain
     *
     * @return array
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
}
