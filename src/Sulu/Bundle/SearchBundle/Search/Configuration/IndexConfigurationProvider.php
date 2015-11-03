<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Search\Configuration;

/**
 * Provides IndexConfigurations from the Symfony configuration stored in the container.
 */
class IndexConfigurationProvider implements IndexConfigurationProviderInterface
{
    /**
     * @var string
     */
    private $indexConfigurations = [];

    /**
     * @param array $indexConfiguration
     */
    public function __construct($indexConfigurations)
    {
        foreach ($indexConfigurations as $indexName => $indexConfiguration) {
            $this->indexConfigurations[$indexName] = new IndexConfiguration(
                $indexName,
                $indexConfiguration['security_context']
            );
        }
    }

    /**
     * Returns all IndexConfigurations available in this installation.
     *
     * @return IndexConfiguration[]
     */
    public function getIndexConfigurations()
    {
        return $this->indexConfigurations;
    }

    /**
     * Returns the IndexConfiguration for the index with the given name.
     *
     * @param string $name The name of the index to get the IndexConfiguration from
     *
     * @return IndexConfiguration
     */
    public function getIndexConfiguration($name)
    {
        if (!array_key_exists($name, $this->indexConfigurations)) {
            return null;
        }

        return $this->indexConfigurations[$name];
    }
}
