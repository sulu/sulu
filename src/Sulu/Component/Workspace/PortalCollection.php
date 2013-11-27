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
 * A collection of all portals in a specific sulu installation
 * @package Sulu\Component\Portal
 */
class PortalCollection implements \IteratorAggregate
{
    /**
     * All the portals in a specific sulu installation
     * @var Portal[]
     */
    private $portals;

    /**
     * Contains all the resources, which where used to build this collection.
     * Is required by the Symfony CacheConfig-Component.
     * @var FileResource[]
     */
    private $resources;

    /**
     * Adds the portal with its unique key as array key to the collection
     * @param Portal $portal The portal to add
     */
    public function add(Portal $portal, $environment = 'prod')
    {
        $this->portals[$portal->getKey()] = $portal;
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
    public function get($key)
    {
        return $this->portals[$key];
    }

    /**
     * Returns the length of the collection
     * @return int
     */
    public function length()
    {
        return count($this->portals);
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
        return new \ArrayIterator($this->portals);
    }

    /**
     * Returns the content of these portals as array
     * @return array
     */
    public function toArray()
    {
        $portals = array();

        foreach ($this->portals as $portal) {
            $portalData = array();
            $portalData['name'] = $portal->getName();
            $portalData['key'] = $portal->getKey();
            $portalData['resourceLocator']['strategy'] = $portal->getResourceLocatorStrategy();

            $portalData['localizations'] = $this->toArrayLocalizations($portal->getLocalizations());

            $portalData['theme']['key'] = $portal->getTheme()->getKey();
            $portalData['theme']['excludedTemplates'] = $portal->getTheme()->getExcludedTemplates();

            foreach ($portal->getEnvironments() as $environment) {
                $environmentData = array();
                $environmentData['type'] = $environment->getType();

                foreach ($environment->getUrls() as $url) {
                    $urlData = array();
                    $urlData['url'] = $url->getUrl();
                    $urlData['main'] = $url->isMain();

                    $environmentData['urls'][] = $urlData;
                }
                $portalData['environments'][] = $environmentData;
            }

            $workspaceData['key'] = $portal->getWorkspace()->getKey();
            $workspaceData['name'] = $portal->getWorkspace()->getName();
            $workspaceData['localizations'] = $this->toArrayLocalizations($portal->getWorkspace()->getLocalizations());

            $segments = $portal->getWorkspace()->getSegments();
            if (!empty($segments)) {
                foreach ($segments as $segment) {
                    $segmentData = array();
                    $segmentData['key'] = $segment->getKey();
                    $segmentData['name'] = $segment->getName();

                    $workspaceData['segments'][] = $segmentData;
                }
            }

            $portalData['workspace'] = $workspaceData;

            $portals[] = $portalData;
        }

        return $portals;
    }

    /**
     * @param $localizations Localization[]
     * @internal param $portal
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
}
