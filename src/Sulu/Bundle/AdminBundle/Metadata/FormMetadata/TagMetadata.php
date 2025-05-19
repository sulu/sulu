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
     * @var array<string, string>
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

    /**
     * @param array<string, string> $attributes
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return mixed
     */
    public function getAttribute(string $name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function hasAttributes(array $attributes): bool
    {
        foreach ($attributes as $key => $value) {
            if (!\array_key_exists($key, $this->attributes) || $this->attributes[$key] !== $value) {
                return false;
            }
        }

        return true;
    }
}
