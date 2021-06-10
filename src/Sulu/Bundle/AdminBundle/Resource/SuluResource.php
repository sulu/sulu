<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Resource;

class SuluResource implements \JsonSerializable
{
    /**
     * @var string
     */
    private $resourceKey;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string|null
     */
    private $title;

    /**
     * @var SuluResource[]
     */
    private $children;

    public function __construct(string $resourceKey, string $id, ?string $title = null, array $children = [])
    {
        $this->resourceKey = $resourceKey;
        $this->id = $id;
        $this->title = $title;
        $this->children = $children;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return SuluResource[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            'resourceKey' => $this->resourceKey,
            'id' => $this->id,
            'title' => $this->title,
            'children' => $this->children,
        ];
    }
}
