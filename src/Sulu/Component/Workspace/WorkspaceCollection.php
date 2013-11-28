<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Workspace;

use Symfony\Component\Config\Resource\FileResource;
use Traversable;

/**
 * A collection of all workspaces and portals in a specific sulu installation
 * @package Sulu\Component\Workspace
 */
class WorkspaceCollection implements \IteratorAggregate
{
    /**
     * All the workspaces in a specific sulu installation
     * @var Workspace[]
     */
    private $workspaces;

    /**
     * All the portals in a specific sulu installation
     * @var Portal[]
     */
    private $allPortals;

    /**
     * The portals of this specific sulu installation, prefiltered by the environment and url
     * @var array
     */
    private $environmentPortals;

    /**
     * Contains all the resources, which where used to build this collection.
     * Is required by the Symfony CacheConfig-Component.
     * @var FileResource[]
     */
    private $resources;

    /**
     * Adds the portal with its unique key as array key to the collection for all Portal, and adds all the urls for
     * this portal to the correct environment, with the url as key
     * @param Workspace $workspace The portal to add
     */
    public function add(Workspace $workspace)
    {
        $this->workspaces[$workspace->getKey()] = $workspace;

        foreach ($workspace->getPortals() as $portal) {
            $this->allPortals[$portal->getKey()] = $portal;

            $this->generateUrls($workspace, $portal);
        }
    }

    /**
     * Generates all the urls for a portal
     * @param Workspace $workspace
     * @param Portal $portal
     */
    public function generateUrls(Workspace $workspace, Portal $portal)
    {
        // go through every url, and add the information for the portals
        foreach ($portal->getEnvironments() as $environment) {
            foreach ($environment->getUrls() as $url) {
                // generate urls from pattern
                if ($url->getRedirect() == null) {
                    $this->generatePortalInformation(
                        $portal,
                        $url,
                        $environment->getType(),
                        $workspace->getSegments()
                    );
                } else {
                    $environmentType = $environment->getType();
                    $urlAddress = $url->getUrl();
                    $this->environmentPortals[$environmentType][$urlAddress]['redirect'] = $url->getRedirect();
                    $this->environmentPortals[$environmentType][$urlAddress]['url'] = $urlAddress;
                }
            }
        }
    }

    /**
     * Generates all the possible urls for the given url pattern
     * @param \Sulu\Component\Workspace\Portal $portal
     * @param string|\Sulu\Component\Workspace\Url $url
     * @param string $environment
     * @param Segment[] $segments
     * @internal param \Sulu\Component\Workspace\Workspace $workspace
     */
    private function generatePortalInformation(Portal $portal, Url $url, $environment, $segments)
    {
        foreach ($portal->getLocalizations() as $localization) {
            $urlAddress = $url->getUrl();

            $language = $url->getLanguage() ? $url->getLanguage() : $localization->getLanguage();
            $country = $url->getCountry() ? $url->getCountry() : $localization->getCountry();
            $locale = $language . ($country ? '-' . $country : '');

            $replacers = array(
                '{language}' => $language,
                '{country}' => $country,
                '{localization}' => $locale
            );

            if (!empty($segments)) {
                foreach ($segments as $segment) {
                    $replacers['{segment}'] = $segment->getKey();
                    $urlResult = $this->generateUrlAddress($urlAddress, $replacers);
                    $this->environmentPortals[$environment][$urlResult] = array(
                        'portal' => $portal,
                        'localization' => $localization,
                        'segment' => $segment
                    );
                }
            } else {
                $urlResult = $this->generateUrlAddress($urlAddress, $replacers);
                $this->environmentPortals[$environment][$urlResult] = array(
                    'portal' => $portal,
                    'localization' => $localization
                );
            }
        }
    }

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
        return array_key_exists($key, $this->allPortals) ? $this->allPortals[$key] : null;
    }

    /**
     * Returns all the portals of this collection
     * @param string $environment Returns the portals for the given environment
     * @return Portal[]
     */
    public function getPortals($environment = null)
    {
        return ($environment == null) ? $this->allPortals : $this->environmentPortals[$environment];
    }

    /**
     * Returns the workspace with the given key
     * @param $key string The key of the workspace
     * @return Workspace
     */
    public function getWorkspace($key)
    {
        return array_key_exists($key, $this->workspaces) ? $this->workspaces[$key] : null;
    }

    /**
     * Returns the length of the collection
     * @return int
     */
    public function length()
    {
        return count($this->workspaces);
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
        return new \ArrayIterator($this->workspaces);
    }

    /**
     * Returns the content of these portals as array
     * @return array
     */
    public function toArray()
    {
        $workspaces = array();
        foreach ($this->workspaces as $workspace) {
            $workspaceData = array();
            $workspaceData['key'] = $workspace->getKey();
            $workspaceData['name'] = $workspace->getName();
            $workspaceData['localizations'] = $this->toArrayLocalizations($workspace->getLocalizations());

            $workspaceData = $this->toArraySegments($workspace, $workspaceData);

            $workspaceData['portals'] = array();

            $workspaceData = $this->toArrayPortals($workspace, $workspaceData);
            $workspaces[] = $workspaceData;
        }

        return $workspaces;
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
     * @param Workspace $workspace
     * @param array $workspaceData
     * @return mixed
     */
    private function toArraySegments($workspace, $workspaceData)
    {
        $segments = $workspace->getSegments();
        if (!empty($segments)) {
            foreach ($segments as $segment) {
                $segmentData = array();
                $segmentData['key'] = $segment->getKey();
                $segmentData['name'] = $segment->getName();

                $workspaceData['segments'][] = $segmentData;
            }

            return $workspaceData;
        }

        return $workspaceData;
    }

    /**
     * @param Workspace $workspace
     * @param array $workspaceData
     * @return mixed
     */
    private function toArrayPortals($workspace, $workspaceData)
    {
        foreach ($workspace->getPortals() as $portal) {
            $portalData = array();
            $portalData['name'] = $portal->getName();
            $portalData['key'] = $portal->getKey();
            $portalData['resourceLocator']['strategy'] = $portal->getResourceLocatorStrategy();

            $portalData['localizations'] = $this->toArrayLocalizations($portal->getLocalizations());

            $portalData['theme']['key'] = $portal->getTheme()->getKey();
            $portalData['theme']['excludedTemplates'] = $portal->getTheme()->getExcludedTemplates();

            $portalData = $this->toArrayEnvironments($portal, $portalData);

            $workspaceData['portals'][] = $portalData;
        }

        return $workspaceData;
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
}
