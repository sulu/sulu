<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata;

class DatagridMetadata
{
    /**
     * @var string
     */
    private $resource;

    /**
     * @var string
     */
    private $key;

    /**
     * @var AbstractPropertyMetadata[]
     */
    private $propertiesMetadata = [];

    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function setKey(string $key)
    {
        $this->key = $key;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getPropertiesMetadata()
    {
        return $this->propertiesMetadata;
    }

    public function addPropertyMetadata(AbstractPropertyMetadata $propertyMetadata)
    {
        $this->propertiesMetadata[] = $propertyMetadata;
    }
}
