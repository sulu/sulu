<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Markup\Link;

/**
 * Contains configuration for teaser provider.
 */
class LinkConfiguration implements \JsonSerializable
{
    /**
     * @var string
     */
    private $resourceKey;

    /**
     * @var string
     */
    private $adapter;

    public function __construct(string $resourceKey, string $adapter)
    {
        $this->resourceKey = $resourceKey;
        $this->adapter = $adapter;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function setResourceKey(string $resourceKey): self
    {
        $this->resourceKey = $resourceKey;
        return $this;
    }

    public function getAdapter(): string
    {
        return $this->adapter;
    }

    public function setAdapter(string $adapter): self
    {
        $this->adapter = $adapter;
        return $this;
    }

    function jsonSerialize(): array
    {
        return [
            'resourceKey' => $this->getResourceKey(),
            'adapter' => $this->getAdapter(),
        ];
    }
}
