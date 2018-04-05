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
use Sulu\Bundle\AdminBundle\ResourceMetadata\Type\Type;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Type\TypesInterface;

class TypedResourceMetadata implements ResourceMetadataInterface, DatagridInterface, TypesInterface, EndpointInterface
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
     * @var Type[]
     */
    private $types = [];

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

    public function getDatagrid(): Datagrid
    {
        return $this->datagrid;
    }

    public function setDatagrid(Datagrid $datagrid): void
    {
        $this->datagrid = $datagrid;
    }

    /**
     * @return Type[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function addType(Type $type): void
    {
        $this->types[$type->getName()] = $type;
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
