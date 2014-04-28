<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Manager;

use Psr\Log\LoggerInterface;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Loader\Exception\InvalidUrlDefinitionException;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class WebspaceCollectionBuilder
{
    const REPLACER_LANGUAGE = '{language}';
    const REPLACER_COUNTRY = '{country}';
    const REPLACER_LOCALIZATION = '{localization}';
    const REPLACER_SEGMENT = '{segment}';

    private $replacers = array(
        self::REPLACER_LANGUAGE,
        self::REPLACER_COUNTRY,
        self::REPLACER_LOCALIZATION,
        self::REPLACER_SEGMENT
    );

    /**
     * The loader for the xml config files
     * @var LoaderInterface
     */
    private $loader;

    /**
     * Logger for logging the warnings
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The path to the xml config files
     * @var string
     */
    private $path;

    /**
     * The webspaces for the configured path
     * @var Webspace[]
     */
    private $webspaces;

    /**
     * The portals for the configured path
     * @var Portal[]
     */
    private $portals;

    /**
     * The portal informations for the configured path
     * @var PortalInformation[][]
     */
    private $portalInformations;

    /**
     * @param LoaderInterface $loader The loader for the xml config files
     * @param LoggerInterface $logger For logging the warnings
     * @param $path string The path to the xml config files
     */
    public function __construct(LoaderInterface $loader, LoggerInterface $logger, $path)
    {
        $this->loader = $loader;
        $this->logger = $logger;
        $this->path = $path;
    }

    public function build()
    {
        $finder = new Finder();
        $finder->in($this->path)->files()->name('*.xml');

        // Iterate over config files, and add a portal object for each config to the collection
        $collection = new WebspaceCollection();

        // reset arrays
        $this->webspaces = array();
        $this->portals = array();
        $this->portalInformations = array();

        foreach ($finder as $file) {
            /** @var SplFileInfo $file */
            try {
                // add file resource for cache invalidation
                $collection->addResource(new FileResource($file->getRealPath()));

                /** @var Webspace $webspace */
                $webspace = $this->loader->load($file->getRealPath());
                $this->webspaces[] = $webspace;

                $this->buildPortals($webspace);
            } catch (\InvalidArgumentException $iae) {
                $this->logger->warning(
                    'The file "' . $file->getRealPath() . '" does not match the schema and was skipped'
                );
            } catch (InvalidUrlDefinitionException $iude) {
                $this->logger->warning(
                    'The file "' . $file->getRealPath() . '" defined some invalid urls and was skipped'
                );
            }
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
            $this->buildUrls($portal, $environment);
        }
    }

    private function buildUrls(Portal $portal, Environment $environment)
    {
        $segments = $portal->getWebspace()->getSegments();

        foreach ($environment->getUrls() as $url) {
            $urlAddress = $url->getUrl();
            $urlRedirect = $url->getRedirect();
            if ($urlRedirect == null) {
                // create all the urls for every localization/segment combination
                foreach ($portal->getLocalizations() as $localization) {
                    $language = $url->getLanguage() ? $url->getLanguage() : $localization->getLanguage();
                    $country = $url->getCountry() ? $url->getCountry() : $localization->getCountry();
                    $locale = $language . ($country ? '-' . $country : '');

                    $replacers = array(
                        self::REPLACER_LANGUAGE => $language,
                        self::REPLACER_COUNTRY => $country,
                        self::REPLACER_LOCALIZATION => $locale
                    );

                    $this->buildUrlFullMatch($portal, $environment, $segments, $replacers, $urlAddress, $localization);
                    $this->buildUrlPartialMatch($portal, $environment, $urlAddress);
                }
            } else {
                // create the redirect
                $this->buildUrlRedirect($portal->getWebspace(), $environment, $urlAddress, $urlRedirect);
            }
        }
    }

    /**
     * @param Webspace $webspace
     * @param Environment $environment
     * @param string $urlAddress
     * @param string $urlRedirect
     */
    private function buildUrlRedirect(Webspace $webspace, Environment $environment, $urlAddress, $urlRedirect)
    {
        $this->portalInformations[$environment->getType()][$urlAddress] = new PortalInformation(
            PortalInformation::TYPE_REDIRECT,
            $webspace,
            null,
            null,
            $urlAddress,
            null,
            $urlRedirect
        );
    }

    /**
     * @param Portal $portal
     * @param Environment $environment
     * @param Segment[] $segments
     * @param string[] $replacers
     * @param string $urlAddress
     * @param Localization $localization
     */
    private function buildUrlFullMatch(
        Portal $portal,
        Environment $environment,
        $segments,
        $replacers,
        $urlAddress,
        Localization $localization
    )
    {
        if (!empty($segments)) {
            foreach ($segments as $segment) {
                $replacers[self::REPLACER_SEGMENT] = $segment->getKey();
                $urlResult = $this->generateUrlAddress($urlAddress, $replacers);
                $this->portalInformations[$environment->getType()][$urlResult] = new PortalInformation(
                    PortalInformation::TYPE_FULL_MATCH,
                    $portal->getWebspace(),
                    $portal,
                    $localization,
                    $urlResult,
                    $segment
                );
            }
        } else {
            $urlResult = $this->generateUrlAddress($urlAddress, $replacers);
            $this->portalInformations[$environment->getType()][$urlResult] = new PortalInformation(
                PortalInformation::TYPE_FULL_MATCH,
                $portal->getWebspace(),
                $portal,
                $localization,
                $urlResult
            );
        }
    }

    private function buildUrlPartialMatch(Portal $portal, Environment $environment, $urlAddress)
    {
        $urlResult = $this->removeUrlPlaceHolders($urlAddress);

        if ($this->validateUrlPartialMatch($urlResult, $environment)) {
            $this->portalInformations[$environment->getType()][$urlResult] = new PortalInformation(
                PortalInformation::TYPE_PARTIAL_MATCH,
                $portal->getWebspace(),
                $portal,
                $portal->getDefaultLocalization(),
                $urlResult,
                $portal->getWebspace()->getDefaultSegment()
            );
        }
    }

    /**
     * @param $urlResult
     * @param Environment $environment
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
     * Replaces the given values in the pattern
     * @param string $pattern
     * @param array $replacers
     * @return string
     */
    private function generateUrlAddress($pattern, $replacers)
    {
        foreach ($replacers as $replacer => $value) {
            $pattern = str_replace($replacer, $value, $pattern);
        }

        return $pattern;
    }

    /**
     * Removes the placesholders from the url address
     * @param $pattern
     * @return mixed|string
     */
    private function removeUrlPlaceHolders($pattern)
    {
        foreach ($this->replacers as $replacer) {
            $pattern = str_replace($replacer, '', $pattern);
        }

        $pattern = ltrim($pattern, '.');
        $pattern = rtrim($pattern, '/');

        return $pattern;
    }
}
