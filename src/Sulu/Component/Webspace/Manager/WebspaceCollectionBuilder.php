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
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Exception\InvalidTemplateException;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Finder\Finder;

class WebspaceCollectionBuilder
{
    /**
     * The webspaces for the configured path.
     *
     * @var array<string, Webspace>
     */
    private $webspaces;

    /**
     * The portals for the configured path.
     *
     * @var array<Portal>
     */
    private $portals;

    /**
     * The portal informations for the configured path.
     *
     * @var PortalInformation[][]
     */
    private $portalInformations;

    /**
     * @param string $path
     * @param array<string> $availableTemplates
     */
    public function __construct(
        private LoaderInterface $loader,
        private ReplacerInterface $urlReplacer,
        private $path,
        private array $availableTemplates
    ) {
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

            foreach ($webspace->getDefaultTemplates() as $defaultTemplate) {
                if (!\in_array($defaultTemplate, $this->availableTemplates)) {
                    throw new InvalidTemplateException($webspace, $defaultTemplate);
                }

                if (\in_array($defaultTemplate, $webspace->getExcludedTemplates())) {
                    throw new InvalidTemplateException($webspace, $defaultTemplate);
                }
            }

            $this->webspaces[$webspace->getKey()] = $webspace;

            $this->buildPortals($webspace);
        }

        \uasort(
            $this->webspaces,
            function(Webspace $a, Webspace $b) {
                return \strnatcasecmp((string) $a->getName(), (string) $b->getName());
            }
        );

        $environments = \array_keys($this->portalInformations);

        foreach ($environments as $environment) {
            // sort all portal informations by length
            \uksort(
                $this->portalInformations[$environment],
                function($a, $b) {
                    return \strlen($a) < \strlen($b) ? 1 : -1;
                }
            );
        }

        $collection->setWebspaces($this->webspaces);
        $collection->setPortals($this->portals);
        $collection->setPortalInformations($this->portalInformations);

        return $collection;
    }

    private function buildPortals(Webspace $webspace): void
    {
        foreach ($webspace->getPortals() as $portal) {
            $this->portals[] = $portal;

            $this->buildEnvironments($portal);
        }
    }

    private function buildEnvironments(Portal $portal): void
    {
        foreach ($portal->getEnvironments() as $environment) {
            $this->buildEnvironment($portal, $environment);
        }
    }

    private function buildEnvironment(Portal $portal, Environment $environment): void
    {
        foreach ($environment->getUrls() as $url) {
            $urlAddress = $url->getUrl();
            $urlRedirect = $url->getRedirect();
            if (null == $urlRedirect) {
                $this->buildUrls($portal, $environment, $url, $urlAddress);
            } else {
                // create the redirect
                $this->buildUrlRedirect(
                    $portal->getWebspace(),
                    $environment,
                    $portal,
                    $urlAddress,
                    $urlRedirect,
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
                false,
                $urlAddress,
                1
            );
        }
    }

    /**
     * @param string $urlAddress
     * @param string $urlRedirect
     */
    private function buildUrlRedirect(
        Webspace $webspace,
        Environment $environment,
        Portal $portal,
        $urlAddress,
        $urlRedirect,
        Url $url
    ): void {
        $this->portalInformations[$environment->getType()][$urlAddress] = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_REDIRECT,
            $webspace,
            $portal,
            null,
            $urlAddress,
            $urlRedirect,
            $url->isMain(),
            $url->getUrl(),
            $this->urlReplacer->hasHostReplacer($urlAddress) ? 4 : 9
        );
    }

    /**
     * @param array<string, string> $replacers
     * @param string $urlAddress
     */
    private function buildUrlFullMatch(
        Portal $portal,
        Environment $environment,
        array $replacers,
        $urlAddress,
        Localization $localization,
        Url $url
    ): void {
        $urlResult = $this->generateUrlAddress($urlAddress, $replacers);
        $this->portalInformations[$environment->getType()][$urlResult] = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $portal->getWebspace(),
            $portal,
            $localization,
            $urlResult,
            null,
            $url->isMain(),
            $url->getUrl(),
            $this->urlReplacer->hasHostReplacer($urlResult) ? 5 : 10
        );
    }

    /**
     * @param string $urlAddress
     */
    private function buildUrlPartialMatch(
        Portal $portal,
        Environment $environment,
        $urlAddress,
        Url $url
    ): void {
        $replacers = [];

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
                $urlRedirect,
                false, // partial matches cannot be main
                $url->getUrl(),
                $this->urlReplacer->hasHostReplacer($urlResult) ? 4 : 9
            );
        }
    }

    /**
     * Builds the URLs for the portal, which are not a redirect.
     *
     * @param string $urlAddress
     */
    private function buildUrls(
        Portal $portal,
        Environment $environment,
        Url $url,
        $urlAddress
    ): void {
        if ($url->getLanguage()) {
            $language = $url->getLanguage();
            $country = $url->getCountry();
            $locale = $language . ($country ? '_' . $country : '');

            $replacers = [
                ReplacerInterface::REPLACER_LANGUAGE => $language,
                ReplacerInterface::REPLACER_COUNTRY => $country,
                ReplacerInterface::REPLACER_LOCALIZATION => $locale,
            ];

            $this->buildUrlFullMatch(
                $portal,
                $environment,
                $replacers,
                $urlAddress,
                $portal->getLocalization($locale),
                $url
            );
        } else {
            // create all the urls for every localization combination
            foreach ($portal->getLocalizations() as $localization) {
                $language = $url->getLanguage() ? $url->getLanguage() : $localization->getLanguage();
                $country = $url->getCountry() ? $url->getCountry() : $localization->getCountry();

                $replacers = [
                    ReplacerInterface::REPLACER_LANGUAGE => $language,
                    ReplacerInterface::REPLACER_COUNTRY => $country,
                    ReplacerInterface::REPLACER_LOCALIZATION => $localization->getLocale(Localization::DASH),
                ];

                $this->buildUrlFullMatch(
                    $portal,
                    $environment,
                    $replacers,
                    $urlAddress,
                    $localization,
                    $url
                );
            }
        }

        $this->buildUrlPartialMatch(
            $portal,
            $environment,
            $urlAddress,
            $url
        );
    }

    private function validateUrlPartialMatch(string $urlResult, Environment $environment): bool
    {
        return
            // only valid if there is no full match already
            !\array_key_exists($urlResult, $this->portalInformations[$environment->getType()])
            // check if last character is no dot
            && '.' != \substr($urlResult, -1);
    }

    /**
     * Replaces the given values in the pattern.
     *
     * @param array<string, string> $replacers
     */
    private function generateUrlAddress(string $pattern, array $replacers): string
    {
        foreach ($replacers as $replacer => $value) {
            $pattern = $this->urlReplacer->replace($pattern, $replacer, $value);
        }

        return $pattern;
    }
}
