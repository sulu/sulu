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

    public function __construct(string $name, bool $mandatory, SchemaMetadata $schemaMetadata)
    {
        parent::__construct($name, $mandatory);
        $this->schemaMetadata = $schemaMetadata;
    }

    public function toJsonSchema(): ?array
    {
        $jsonSchema = [
            'name' => $this->getName(),
            'type' => 'array',
            'items' => $this->schemaMetadata->toJsonSchema(),
        ];

        return $jsonSchema;
    }
}
