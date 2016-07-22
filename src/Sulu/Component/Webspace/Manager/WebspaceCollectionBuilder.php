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

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Finder\Finder;

class WebspaceCollectionBuilder
{
    /**
     * The loader for the xml config files.
     *
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var ReplacerInterface
     */
    private $urlReplacer;

    /**
     * The path to the xml config files.
     *
     * @var string
     */
    private $path;

    /**
     * The webspaces for the configured path.
     *
     * @var Webspace[]
     */
    private $webspaces;

    /**
     * The portals for the configured path.
     *
     * @var Portal[]
     */
    private $portals;

    /**
     * The portal informations for the configured path.
     *
     * @var PortalInformation[][]
     */
    private $portalInformations;

    /**
     * @param LoaderInterface $loader The loader for the xml config files
     * @param ReplacerInterface $urlReplacer Factory for url-replacers
     * @param $path string The path to the xml config files
     */
    public function __construct(
        LoaderInterface $loader,
        ReplacerInterface $urlReplacer,
        $path
    ) {
        $this->loader = $loader;
        $this->urlReplacer = $urlReplacer;
        $this->path = $path;
    }

    public function build()
    {
        $finder = new Finder();
        $finder->in($this->path)->files()->name('*.xml')->sortByName();

        // Iterate over config files, and add a portal object for each config to the collection
        $collection = new WebspaceCollection();

        // reset arrays
        $this->webspaces = [];
        $this->portals = [];
        $this->portalInformations = [];

        foreach ($finder as $file) {
            // add file resource for cache invalidation
            $collection->addResource(new FileResource($file->getRealPath()));

            /** @var Webspace $webspace */
            $webspace = $this->loader->load($file->getRealPath());
            $this->webspaces[] = $webspace;

            $this->buildPortals($webspace);
        }

        $environments = array_keys($this->portalInformations);

        foreach ($environments as $environment) {
            // sort all portal informations by length
            uksort(
                $this->portalInformations[$environment],
                function ($a, $b) {
                    return strlen($a) < strlen($b);
                }
            );
        }

        $collection->setWebspaces($this->webspaces);
        $collection->setPortals($this->portals);
        $collection->setPortalInformations($this->portalInformations);

        return $collection;
    }

    /**
     * @param Webspace $webspace
     */
    private function buildPortals(Webspace $webspace)
    {
        foreach ($webspace->getPortals() as $portal) {
            $this->portals[] = $portal;

            $this->buildEnvironments($portal);
        }
    }

    private function buildEnvironments(Portal $portal)
    {
        foreach ($portal->getEnvironments() as $environment) {
            $this->buildEnvironment($portal, $environment);
        }
    }

    private function buildEnvironment(Portal $portal, Environment $environment)
    {
        $segments = $portal->getWebspace()->getSegments();

        foreach ($environment->getUrls() as $url) {
            $urlAddress = $url->getUrl();
            $urlRedirect = $url->getRedirect();
            $urlAnalyticsKey = $url->getAnalyticsKey();
            if ($urlRedirect == null) {
                $this->buildUrls($portal, $environment, $url, $segments, $urlAddress, $urlAnalyticsKey);
            } else {
                // create the redirect
                $this->buildUrlRedirect(
                    $portal->getWebspace(),
                    $environment,
                    $portal,
                    $urlAddress,
                    $urlRedirect,
                    $urlAnalyticsKey,
                    $url
                );
            }
        }

        foreach ($environment->getCustomUrls() as $customUrl) {
            $urlAddress = $customUrl->getUrl();
            $this->portalInformations[$environment->getType()][$urlAddress] = new PortalInformation(
                RequestAnalyzerInterface::MATCH_TYPE_WILDCARD,
                $portal->getWebspace(),
                $portal,
                null,
                $urlAddress,
                null,
                null,
                null,
                false,
                $urlAddress,
                1
            );
        }
    }

    /**
     * @param Webspace $webspace
     * @param Environment $environment
     * @param Portal $portal
     * @param string $urlAddress
     * @param string $urlRedirect
     * @param string $urlAnalyticsKey
     * @param Url $url
     */
    private function buildUrlRedirect(
        Webspace $webspace,
        Environment $environment,
        Portal $portal,
        $urlAddress,
        $urlRedirect,
        $urlAnalyticsKey,
        Url $url
    ) {
        $this->portalInformations[$environment->getType()][$urlAddress] = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_REDIRECT,
            $webspace,
            $portal,
            null,
            $urlAddress,
            null,
            $urlRedirect,
            $urlAnalyticsKey,
            $url->isMain(),
            $url->getUrl(),
            $this->urlReplacer->hasHostReplacer($urlAddress) ? 4 : 9
        );
    }

    /**
     * @param Portal $portal
     * @param Environment $environment
     * @param Segment[] $segments
     * @param string[] $replacers
     * @param string $urlAddress
     * @param Localization $localization
     * @param string $urlAnalyticsKey
     * @param Url $url
     */
    private function buildUrlFullMatch(
        Portal $portal,
        Environment $environment,
        $segments,
        $replacers,
        $urlAddress,
        Localization $localization,
        $urlAnalyticsKey,
        Url $url
    ) {
        if (!empty($segments)) {
            foreach ($segments as $segment) {
                $replacers[ReplacerInterface::REPLACER_SEGMENT] = $segment->getKey();
                $urlResult = $this->generateUrlAddress($urlAddress, $replacers);
                $this->portalInformations[$environment->getType()][$urlResult] = new PortalInformation(
                    RequestAnalyzerInterface::MATCH_TYPE_FULL,
                    $portal->getWebspace(),
                    $portal,
                    $localization,
                    $urlResult,
                    $segment,
                    null,
                    $urlAnalyticsKey,
                    $url->isMain(),
                    $url->getUrl(),
                    $this->urlReplacer->hasHostReplacer($urlResult) ? 5 : 10
                );
            }
        } else {
            $urlResult = $this->generateUrlAddress($urlAddress, $replacers);
            $this->portalInformations[$environment->getType()][$urlResult] = new PortalInformation(
                RequestAnalyzerInterface::MATCH_TYPE_FULL,
                $portal->getWebspace(),
                $portal,
                $localization,
                $urlResult,
                null,
                null,
                $urlAnalyticsKey,
                $url->isMain(),
                $url->getUrl(),
                $this->urlReplacer->hasHostReplacer($urlResult) ? 5 : 10
            );
        }
    }

    /**
     * @param Portal $portal
     * @param Environment $environment
     * @param string $urlAddress
     * @param string $urlAnalyticsKey
     * @param Url $url
     */
    private function buildUrlPartialMatch(
        Portal $portal,
        Environment $environment,
        $urlAddress,
        $urlAnalyticsKey,
        Url $url
    ) {
        $replacers = [];

        $defaultSegment = $portal->getWebspace()->getDefaultSegment();
        if ($defaultSegment) {
            $replacers[ReplacerInterface::REPLACER_SEGMENT] = $defaultSegment->getKey();
        }

        $urlResult = $this->urlReplacer->cleanup(
            $urlAddress,
            [
                ReplacerInterface::REPLACER_LANGUAGE,
                ReplacerInterface::REPLACER_COUNTRY,
                ReplacerInterface::REPLACER_LOCALIZATION,
                ReplacerInterface::REPLACER_SEGMENT,
            ]
        );
        $urlRedirect = $this->generateUrlAddress($urlAddress, $replacers);

        if ($this->validateUrlPartialMatch($urlResult, $environment)) {
            $this->portalInformations[$environment->getType()][$urlResult] = new PortalInformation(
                RequestAnalyzerInterface::MATCH_TYPE_PARTIAL,
                $portal->getWebspace(),
                $portal,
                null,
                $urlResult,
                $portal->getWebspace()->getDefaultSegment(),
                $urlRedirect,
                $urlAnalyticsKey,
                false, // partial matches cannot be main
                $url->getUrl(),
                $this->urlReplacer->hasHostReplacer($urlResult) ? 4 : 9
            );
        }
    }

    /**
     * Builds the URLs for the portal, which are not a redirect.
     *
     * @param Portal $portal
     * @param Environment $environment
     * @param $url
     * @param $segments
     * @param $urlAddress
     * @param $urlAnalyticsKey
     */
    private function buildUrls(
        Portal $portal,
        Environment $environment,
        Url $url,
        $segments,
        $urlAddress,
        $urlAnalyticsKey
    ) {
        if ($url->getLanguage()) {
            $language = $url->getLanguage();
            $country = $url->getCountry();
            $locale = $language . ($country ? '_' . $country : '');

            $this->buildUrlFullMatch(
                $portal,
                $environment,
                $segments,
                [],
                $urlAddress,
                $portal->getLocalization($locale),
                $urlAnalyticsKey,
                $url
            );
        } else {
            // create all the urls for every localization/segment combination
            foreach ($portal->getLocalizations() as $localization) {
                $language = $url->getLanguage() ? $url->getLanguage() : $localization->getLanguage();
                $country = $url->getCountry() ? $url->getCountry() : $localization->getCountry();

                $replacers = [
                    ReplacerInterface::REPLACER_LANGUAGE => $language,
                    ReplacerInterface::REPLACER_COUNTRY => $country,
                    ReplacerInterface::REPLACER_LOCALIZATION => $localization->getLocalization('-'),
                ];

                $this->buildUrlFullMatch(
                    $portal,
                    $environment,
                    $segments,
                    $replacers,
                    $urlAddress,
                    $localization,
                    $urlAnalyticsKey,
                    $url
                );
            }
            $this->buildUrlPartialMatch(
                $portal,
                $environment,
                $urlAddress,
                $urlAnalyticsKey,
                $url
            );
        }
    }

    /**
     * @param $urlResult
     * @param Environment $environment
     *
     * @return bool
     */
    private function validateUrlPartialMatch($urlResult, Environment $environment)
    {
        return
            // only valid if there is no full match already
            !array_key_exists($urlResult, $this->portalInformations[$environment->getType()])
            // check if last character is no dot
            && substr($urlResult, -1) != '.';
    }

    /**
     * Replaces the given values in the pattern.
     *
     * @param string $pattern
     * @param array $replacers
     *
     * @return string
     */
    private function generateUrlAddress($pattern, $replacers)
    {
        foreach ($replacers as $replacer => $value) {
            $pattern = $this->urlReplacer->replace($pattern, $replacer, $value);
        }

        return $pattern;
    }
}
