<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

class TagMetadata
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int|null
     */
    private $priority;

    /**
     * @var array
     */
    private $attributes = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): void
    {
        $this->priority = $priority;
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttribute(string $name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function matchAttributes(array $attributes): bool
    {
        foreach ($this->attributes as $key => $value) {
            if (($attributes[$key] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }
}
