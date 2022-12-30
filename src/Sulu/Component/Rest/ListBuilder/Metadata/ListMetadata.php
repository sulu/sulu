<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata;

class ListMetadata
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

    /**
     * @param string $resource
     *
     * @return void
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return void
     */
    public function setKey(string $key)
    {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return AbstractPropertyMetadata[]
     */
    public function getPropertiesMetadata()
    {
        return $this->propertiesMetadata;
    }

    /**
     * @return void
     */
    public function addPropertyMetadata(AbstractPropertyMetadata $propertyMetadata)
    {
        $this->propertiesMetadata[] = $propertyMetadata;
    }
}
