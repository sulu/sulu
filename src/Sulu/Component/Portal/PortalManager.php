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

use Sulu\Component\Portal\PortalCollection;
use Sulu\Component\Portal\Portal;
use Sulu\Component\Portal\Dumper\PhpPortalCollectionDumper;
use Symfony\Component\Config\ConfigCache;

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

    public function __construct($options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Return the portal with the given key, or null, if it does not exist
     * @param $key The key to look for
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
     * Returns the portals from the cache, or creates the cache.
     * @return PortalCollection
     */
    private function getPortals()
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
                $cache->write($dumper->dump(
                        array(
                            'cache_class' => $class,
                            'base_class' => $this->options['base_class']
                        )
                    ),
                    $portalCollection->getResources()
                );
            }

            require_once($cache);

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
    private function buildPortalCollection()
    {
        $collection = new PortalCollection();

        //TODO add real portals from config
        $portal = new Portal();
        $portal->setName('Sulu');
        $portal->setKey('sulu');

        $collection->add($portal);

        $portal = new Portal();
        $portal->setName('Sulu2');
        $portal->setKey('sulu2');

        $collection->add($portal);

        return $collection;
    }
}
