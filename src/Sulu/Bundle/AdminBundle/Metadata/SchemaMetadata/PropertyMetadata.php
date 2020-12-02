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
     * @var SchemaMetadata|null
     */
    private $schemaMetadata;

    public function __construct(string $name, bool $mandatory, ?SchemaMetadata $schemaMetadata = null)
    {
        $this->name = $name;
        $this->mandatory = $mandatory;
        $this->schemaMetadata = $schemaMetadata;
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
        if (null !== $this->schemaMetadata) {
            $jsonSchema = $this->schemaMetadata->toJsonSchema();
            $jsonSchema['name'] = $this->name;

            return $jsonSchema;
        }

        return null;
    }
}
