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

class ArrayMetadata implements SchemaMetadataInterface
{
    private \Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadataInterface $schemaMetadata;

    private ?int $minItems = null;

    private ?int $maxItems = null;

    private ?bool $uniqueItems = null;

    public function __construct(
        SchemaMetadataInterface $schemaMetadata,
        int $minItems = null,
        int $maxItems = null,
        bool $uniqueItems = null
    ) {
        $this->schemaMetadata = $schemaMetadata;
        $this->minItems = $minItems;
        $this->maxItems = $maxItems;
        $this->uniqueItems = $uniqueItems;
    }

    public function toJsonSchema(): array
    {
        $jsonSchema = [
            'type' => 'array',
            'items' => $this->schemaMetadata->toJsonSchema(),
        ];

        if (null !== $this->minItems) {
            $jsonSchema['minItems'] = $this->minItems;
        }

        if (null !== $this->maxItems) {
            $jsonSchema['maxItems'] = $this->maxItems;
        }

        if (null !== $this->uniqueItems) {
            $jsonSchema['uniqueItems'] = $this->uniqueItems;
        }

        return $jsonSchema;
    }
}
