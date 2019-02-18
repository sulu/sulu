<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata;

class Property
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
     * @var string|number|null
     */
    private $value;

    public function __construct(string $name, bool $mandatory, $value = null)
    {
        $this->name = $name;
        $this->mandatory = $mandatory;
        $this->value = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    public function toJsonSchema(): ?array
    {
        if (null === $this->value) {
            return null;
        }

        return [
            'name' => $this->name,
            'const' => $this->value,
        ];
    }
}
