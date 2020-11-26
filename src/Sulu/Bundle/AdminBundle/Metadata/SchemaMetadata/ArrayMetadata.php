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

class ArrayMetadata extends PropertyMetadata
{
    /**
     * @var SchemaMetadata
     */
    private $schemaMetadata;

    /**
     * @var int|null
     */
    private $minItems;

    /**
     * @var bool|null
     */
    private $uniqueItems;

    public function __construct(
        string $name,
        bool $mandatory,
        SchemaMetadata $schemaMetadata,
        int $minItems = null,
        bool $uniqueItems = null
    ) {
        parent::__construct($name, $mandatory, 'array');

        $this->schemaMetadata = $schemaMetadata;
        $this->minItems = $minItems;
        $this->uniqueItems = $uniqueItems;
    }

    public function toJsonSchema(): ?array
    {
        $jsonSchema = [
            'name' => $this->getName(),
            'type' => 'array',
            'items' => $this->schemaMetadata->toJsonSchema(),
        ];

        if (null !== $this->minItems) {
            $jsonSchema['minItems'] = $this->minItems;
        }

        if (null !== $this->uniqueItems) {
            $jsonSchema['uniqueItems'] = $this->uniqueItems;
        }

        return $jsonSchema;
    }
}
