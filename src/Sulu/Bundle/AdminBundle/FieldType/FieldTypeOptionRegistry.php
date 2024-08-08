<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\FieldType;

class FieldTypeOptionRegistry implements FieldTypeOptionRegistryInterface
{
    /**
     * @var array<string, array<string, array<mixed>>>
     */
    private $options = [];

    public function add(string $name, string $baseFieldType, array $fieldTypeOptions): void
    {
        if (!\array_key_exists($baseFieldType, $this->options)) {
            $this->options[$baseFieldType] = [];
        }

        $this->options[$baseFieldType][$name] = $fieldTypeOptions;
    }

    /**
     * @return array<string, array<string, array<mixed>>>
     */
    public function toArray(): array
    {
        return $this->options;
    }
}
