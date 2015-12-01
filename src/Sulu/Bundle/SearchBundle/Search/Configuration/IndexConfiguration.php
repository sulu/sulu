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
 * Contains the configuration for an index.
 */
class IndexConfiguration
{
    /**
     * @var string
     */
    private $indexName;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $securityContext;

    /**
     * @param string $indexName The name of the index
     * @param string $name The name of the index for the user interface
     * @param string $securityContext The required security context to access the index
     */
    public function __construct($indexName, $name = null, $securityContext = null)
    {
        $this->indexName = $indexName;
        $this->name = $name;
        $this->securityContext = $securityContext;
    }

    /**
     * Returns the name of the index.
     *
     * @return string
     */
    public function getIndexName()
    {
        return $this->indexName;
    }

    /**
     * Returns the already translated name for the index. Should only be used for non-translatable names. Useful for
     * displaying in the user interface.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the security context required to access index.
     *
     * @return string
     */
    public function getSecurityContext()
    {
        return $this->securityContext;
    }
}
