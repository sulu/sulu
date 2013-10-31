<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Portal;

use Psr\Log\LoggerInterface;
use Sulu\Component\Portal\PortalCollection;
use Sulu\Component\Portal\Portal;
use Sulu\Component\Portal\Dumper\PhpPortalCollectionDumper;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * This class is responsible for loading, reading and caching the portal configuration files
 * @package Sulu\Bundle\CoreBundle\Portal
 */
class PortalManager
{
    /**
     * @var PortalCollection
     */
    private $portals;

    /**
     * @var array
     */
    private $options;

    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoaderInterface $loader, LoggerInterface $logger, $options = array())
    {
        $this->loader = $loader;
        $this->logger = $logger;
        $this->setOptions($options);
    }

    /**
     * Returns the portal with the given key, or null, if it does not exist
     * @param $key string The key to look for
     * @return null|Portal
     */
    public function findByKey($key)
    {
        foreach ($this->getPortals() as $portal) {
            /** @var Portal $portal */
            if ($portal->getKey() == $key) {
                return $portal;
            }
        }

        return null;
    }

    /**
     * Returns the portal for the given url, or null, if it does not exist
     * @param $searchUrl string The url to search for
     * @return null|Portal
     */
    public function findByUrl($searchUrl)
    {
        foreach ($this->getPortals() as $portal) {
            /** @var Portal $portal */
            foreach ($portal->getEnvironments() as $environment) {
                foreach ($environment->getUrls() as $url) {
                    if ($url->getUrl() == $searchUrl) {
                        return $portal;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Returns the portals from the cache, or creates the cache.
     * @return PortalCollection
     */
    public function getPortals()
    {
        if ($this->portals === null) {
            $class = $this->options['cache_class'];
            $cache = new ConfigCache(
                $this->options['cache_dir'] . '/' . $class . '.php',
                $this->options['debug']
            );

            if (!$cache->isFresh()) {
                $portalCollection = $this->buildPortalCollection();
                $dumper = new PhpPortalCollectionDumper($portalCollection);
                $cache->write(
                    $dumper->dump(
                        array(
                            'cache_class' => $class,
                            'base_class' => $this->options['base_class']
                        )
                    ),
                    $portalCollection->getResources()
                );
            }

            require_once $cache;

            $this->portals = new $class();
        }

        return $this->portals;
    }

    /**
     * Sets the options for the manager
     * @param $options
     */
    public function setOptions($options)
    {
        $this->options = array(
            'config_dir' => null,
            'cache_dir' => null,
            'debug' => false,
            'cache_class' => 'PortalCollectionCache',
            'base_class' => 'PortalCollection'
        );

        // overwrite the default values with the given options
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Builds the portal collection from the config
     * @return PortalCollection
     */
    protected function buildPortalCollection()
    {
        // Find portal configs with symfony finder
        $finder = new Finder();
        $finder->in($this->options['config_dir'])->files()->name('*.xml');

        // Iterate over config files, and add a portal object for each config to the collection
        $collection = new PortalCollection();

        foreach ($finder as $file) {
            /** @var SplFileInfo $file */
            try {
                $collection->add($this->loader->load($file->getRealPath()));
                $collection->addResource(new FileResource($file->getRealPath()));
            } catch (\InvalidArgumentException $iae) {
                $this->logger->warning(
                    'The file "' . $file->getRealPath() . '" does not match the schema and was skipped'
                );
            }
        }

        return $collection;
    }
}
