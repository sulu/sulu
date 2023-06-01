<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Search\Configuration;

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
    private $icon;

    /**
     * @var Route
     */
    private $route;

    /**
     * @var string
     */
    private $securityContext;

    /**
     * @var array
     */
    private $contexts;

    public function __construct(
        string $indexName,
        string $icon,
        string $name,
        Route $route,
        ?string $securityContext = null,
        array $contexts = []
    ) {
        $this->indexName = $indexName;
        $this->icon = $icon;
        $this->name = $name;
        $this->route = $route;
        $this->securityContext = $securityContext;
        $this->contexts = $contexts;
    }

    public function getIndexName()
    {
        return $this->indexName;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function getSecurityContext()
    {
        return $this->securityContext;
    }

    public function getContexts()
    {
        return $this->contexts;
    }
}
