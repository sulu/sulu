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
 * Provides an interface to retrieve the Sulu specific search index configuration.
 */
interface IndexConfigurationProviderInterface
{
    /**
     * Returns all IndexConfigurations available in this installation.
     *
     * @return IndexConfiguration[]
     */
    public function getIndexConfigurations();

    /**
     * Returns the IndexConfiguration for the index with the given name.
     *
     * @param string $name The name of the index to get the IndexConfiguration from
     *
     * @return IndexConfiguration
     */
    public function getIndexConfiguration($name);
}
