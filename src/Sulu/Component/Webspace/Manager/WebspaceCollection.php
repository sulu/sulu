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

use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Config\Resource\FileResource;
use Traversable;

/**
 * A collection of all webspaces and portals in a specific sulu installation
 * @package Sulu\Component\Webspace
 */
class WebspaceCollection implements \IteratorAggregate
{
    /**
     * All the webspaces in a specific sulu installation
     * @var Webspace[]
     */
    private $webspaces;

    /**
     * All the portals in a specific sulu installation
     * @var Portal[]
     */
    private $portals;

    /**
     * The portals of this specific sulu installation, prefiltered by the environment and url
     * @var array
     */
    private $portalInformations;

    /**
     * Contains all the resources, which where used to build this collection.
     * Is required by the Symfony CacheConfig-Component.
     * @var FileResource[]
     */
    private $resources;

    /**
     * Adds a new FileResource, which is required to determine if the cache is fresh
     * @param FileResource $resource
     */
    public function addResource(FileResource $resource)
    {
        $this->resources[] = $resource;
    }

    /**
     * Returns the resources used to build this collection
     * @return array The resources build to use this collection
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * Returns the portal with the given index
     * @param $key string The index of the portal
     * @return Portal
     */
    public function getPortal($key)
    {
        return array_key_exists($key, $this->portals) ? $this->portals[$key] : null;
    }

    /**
     * Returns the portal informations for the given environment
     * @param $environment string The environment to deliver
     * @return PortalInformation[]
     */
    public function getPortalInformations($environment)
    {
        if (!isset($this->portalInformations[$environment])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown portal environment "%s"', $environment
            ));
        }

        return $this->portalInformations[$environment];
    }

    /**
     * Returns the webspace with the given key
     * @param $key string The key of the webspace
     * @return Webspace
     */
    public function getWebspace($key)
    {
        return array_key_exists($key, $this->webspaces) ? $this->webspaces[$key] : null;
    }

    /**
     * Returns the length of the collection
     * @return int
     */
    public function length()
    {
        return count($this->webspaces);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->webspaces);
    }

    /**
     * Returns the content of these portals as array
     * @return array
     */
    public function toArray()
    {
        $collection = array();

        $webspaces = array();
        foreach ($this->webspaces as $webspace) {
            $webspaceData = array();
            $webspaceData['key'] = $webspace->getKey();
            $webspaceData['name'] = $webspace->getName();
            $webspaceData['localizations'] = $this->toArrayLocalizations($webspace->getLocalizations());

            $webspaceSecurity = $webspace->getSecurity();
            if ($webspaceSecurity != null) {
                $webspaceData['security']['system'] = $webspaceSecurity->getSystem();
            }

            $webspaceData = $this->toArraySegments($webspace, $webspaceData);

            $webspaceData['portals'] = array();

            $webspaceData['theme']['key'] = $webspace->getTheme()->getKey();
            $webspaceData['theme']['excludedTemplates'] = $webspace->getTheme()->getExcludedTemplates();

            $webspaceData = $this->toArrayPortals($webspace, $webspaceData);
            $webspaces[] = $webspaceData;
        }

        $portalInformations = array();
        foreach ($this->portalInformations as $environment => $environmentPortalInformations) {
            $portalInformations[$environment] = $this->toArrayPortalInformations($environmentPortalInformations);
        }

        $collection['webspaces'] = $webspaces;
        $collection['portalInformations'] = $portalInformations;

        return $collection;
    }

    /**
     * @param $localizations Localization[]
     * @param bool $withAdditionalOptions
     * @return array
     */
    private function toArrayLocalizations($localizations, $withAdditionalOptions = false)
    {
        $localizationsArray = array();

        if (!empty($localizations)) {
            foreach ($localizations as $localization) {
                $localizationData = array();
                $localizationData['country'] = $localization->getCountry();
                $localizationData['language'] = $localization->getLanguage();
                $localizationData['localization'] = $localization->getLocalization();
                $localizationData['default'] = $localization->isDefault();

                if (!$withAdditionalOptions) {
                    $localizationData['children'] = $this->toArrayLocalizations($localization->getChildren(), true);
                    $localizationData['shadow'] = $localization->getShadow();
                }

                $localizationsArray[] = $localizationData;
            }
        }

        return $localizationsArray;
    }

    /**
     * @param Webspace $webspace
     * @param array $webspaceData
     * @return mixed
     */
    private function toArraySegments($webspace, $webspaceData)
    {
        $segments = $webspace->getSegments();
        if (!empty($segments)) {
            foreach ($segments as $segment) {
                $segmentData = array();
                $segmentData['key'] = $segment->getKey();
                $segmentData['name'] = $segment->getName();
                $segmentData['default'] = $segment->isDefault();

                $webspaceData['segments'][] = $segmentData;
            }

            return $webspaceData;
        }

        return $webspaceData;
    }

    /**
     * @param Webspace $webspace
     * @param array $webspaceData
     * @return mixed
     */
    private function toArrayPortals($webspace, $webspaceData)
    {
        foreach ($webspace->getPortals() as $portal) {
            $portalData = array();
            $portalData['name'] = $portal->getName();
            $portalData['key'] = $portal->getKey();
            $portalData['resourceLocator']['strategy'] = $portal->getResourceLocatorStrategy();

            $portalData['localizations'] = $this->toArrayLocalizations($portal->getLocalizations());

            $portalData = $this->toArrayEnvironments($portal, $portalData);

            $webspaceData['portals'][] = $portalData;
        }

        return $webspaceData;
    }

    /**
     * @param Portal $portal
     * @param array $portalData
     * @return mixed
     */
    private function toArrayEnvironments($portal, $portalData)
    {
        foreach ($portal->getEnvironments() as $environment) {
            $environmentData = array();
            $environmentData['type'] = $environment->getType();

            $environmentData = $this->toArrayUrls($environment, $environmentData);
            $portalData['environments'][] = $environmentData;
        }

        return $portalData;
    }

    /**
     * @param Environment $environment
     * @param array $environmentData
     * @return mixed
     */
    private function toArrayUrls($environment, $environmentData)
    {
        foreach ($environment->getUrls() as $url) {
            $urlData = array();
            $urlData['url'] = $url->getUrl();
            $urlData['language'] = $url->getLanguage();
            $urlData['country'] = $url->getCountry();
            $urlData['segment'] = $url->getSegment();
            $urlData['redirect'] = $url->getRedirect();

            $environmentData['urls'][] = $urlData;
        }

        return $environmentData;
    }

    /**
     * @param PortalInformation[] $portalInformations
     * @param array $portalInformationsData
     */
    private function toArrayPortalInformations($portalInformations)
    {
        $portalInformationArray = array();

        foreach ($portalInformations as $portalInformation) {
            $portalInformationData = array();
            $portalInformationData['type'] = $portalInformation->getType();
            $portalInformationData['webspace'] = $portalInformation->getWebspace()->getKey();
            $portalInformationData['url'] = $portalInformation->getUrl();

            $portal = $portalInformation->getPortal();
            if ($portal) {
                $portalInformationData['portal'] = $portal->getKey();
            }

            $localization = $portalInformation->getLocalization();
            if ($localization) {
                $portalInformationData['localization'] = $localization->getLocalization();
            }

            $portalInformationData['redirect'] = $portalInformation->getRedirect();

            $segment = $portalInformation->getSegment();
            if ($segment) {
                $portalInformationData['segment'] = $segment->getKey();
            }

            $portalInformationArray[$portalInformationData['url']] = $portalInformationData;
        }

        return $portalInformationArray;
    }

    /**
     * @param \Sulu\Component\Webspace\Webspace[] $webspaces
     */
    public function setWebspaces($webspaces)
    {
        $this->webspaces = $webspaces;
    }

    /**
     * @return \Sulu\Component\Webspace\Webspace[]
     */
    public function getWebspaces()
    {
        return $this->webspaces;
    }

    /**
     * Returns all the portals of this collection
     * @return array|Portal[]
     */
    public function getPortals()
    {
        return $this->portals;
    }

    /**
     * Sets the portals for this collection
     * @param Portal[] $portals
     */
    public function setPortals($portals)
    {
        $this->portals = $portals;
    }

    /**
     * Sets the portal Information for this collection
     * @param array $portalInformations
     */
    public function setPortalInformations($portalInformations)
    {
        $this->portalInformations = $portalInformations;
    }
}
