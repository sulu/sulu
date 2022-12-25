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
    private string $resource;

    private string $key;

    /**
     * @var AbstractPropertyMetadata[]
     */
    private array $propertiesMetadata = [];

    public function setResource(string $resource): void
    {
        $this->resource = $resource;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return AbstractPropertyMetadata[]
     */
    public function getPropertiesMetadata(): array
    {
        return $this->propertiesMetadata;
    }

    public function addPropertyMetadata(AbstractPropertyMetadata $propertyMetadata): void
    {
        $this->propertiesMetadata[] = $propertyMetadata;
    }
}
