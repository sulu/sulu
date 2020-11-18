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
     * @var array<string, mixed>
     */
    private $jsonSchema = [];

    public function __construct(string $name, bool $mandatory)
    {
        $this->name = $name;
        $this->mandatory = $mandatory;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    /**
     * @param callable|mixed[] $jsonSchema
     */
    public function mergeJsonSchema($jsonSchema): void
    {
        $newJsonSchema = $jsonSchema;

        if (\is_callable($jsonSchema)) {
            $newJsonSchema = $jsonSchema($this->jsonSchema);
        }

        if (\is_array($jsonSchema)) {
            $newJsonSchema = \array_merge($this->jsonSchema, $jsonSchema);
        }

        if (!\is_array($newJsonSchema)) {
            throw new \Exception('Invalid json schema');
        }

        $this->jsonSchema = $newJsonSchema;
    }

    public function toJsonSchema(): ?array
    {
        if (empty($this->jsonSchema)) {
            return null;
        }

        return $this->jsonSchema;
    }
}
