<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata;

class PropertyMetadata
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $mandatory;

    /**
     * @var string|null
     */
    private $type;

    public function __construct(string $name, bool $mandatory, string $type = null)
    {
        $this->name = $name;
        $this->mandatory = $mandatory;
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function toJsonSchema(): ?array
    {
        if (null === $this->type) {
            return null;
        }

        return ['type' => $this->type];
    }
}
