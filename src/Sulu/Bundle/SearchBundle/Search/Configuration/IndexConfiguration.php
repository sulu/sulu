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
    public function __construct(
        private string $indexName,
        private string $icon,
        private string $name,
        private Route $route,
        private ?string $securityContext = null,
        private array $contexts = []
    ) {
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
