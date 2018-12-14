<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\ResourceMetadata;

use Sulu\Bundle\AdminBundle\ResourceMetadata\Datagrid\Datagrid;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Datagrid\DatagridInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Endpoint\EndpointInterface;

class ResourceMetadata implements ResourceMetadataInterface, DatagridInterface, EndpointInterface
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var Datagrid
     */
    private $datagrid;

    /**
     * @var string
     */
    private $endpoint;

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getDatagrid(): ?Datagrid
    {
        return $this->datagrid;
    }

    public function setDatagrid(?Datagrid $datagrid): void
    {
        $this->datagrid = $datagrid;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): void
    {
        $this->endpoint = $endpoint;
    }
}
