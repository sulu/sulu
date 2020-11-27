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

class ObjectMetadata extends PropertyMetadata
{
    /**
     * @var PropertyMetadata[]
     */
    private $properties;

    /**
     * @var SchemaMetadata[]
     */
    private $anyOfs;

    /**
     * @var SchemaMetadata[]
     */
    private $allOfs;

    /**
     * @param PropertyMetadata[] $properties
     * @param SchemaMetadata[] $anyOfs
     * @param SchemaMetadata[] $allOfs
     */
    public function __construct(string $name, bool $mandatory, array $properties = [], array $anyOfs = [], array $allOfs = [], ?string $type = 'object')
    {
        parent::__construct($name, $mandatory, $type);

        $this->properties = $properties;
        $this->anyOfs = $anyOfs;
        $this->allOfs = $allOfs;
    }

    public function toJsonSchema(): ?array
    {
        $jsonSchema = [
            'name' => $this->getName(),
        ];

        if (null !== ($type = $this->getType())) {
            $jsonSchema['type'] = $type;
        }

        $jsonSchema['required'] = \array_values(
            \array_map(
                function(PropertyMetadata $propertyMetadata) {
                    return $propertyMetadata->getName();
                },
                \array_filter(
                    $this->properties,
                    function(PropertyMetadata $propertyMetadata) {
                        return $propertyMetadata->isMandatory();
                    }
                )
            )
        );

        $properties = [];

        foreach ($this->properties as $property) {
            $jsonSchemaProperty = $property->toJsonSchema();
            if (!$jsonSchemaProperty) {
                continue;
            }

            $properties[$property->getName()] = $jsonSchemaProperty;
        }

        if (\count($properties) > 0) {
            $jsonSchema['properties'] = $properties;
        }

        if (\count($this->anyOfs) > 0) {
            $jsonSchema['anyOf'] = \array_map(function(SchemaMetadata $schema) {
                return $schema->toJsonSchema();
            }, $this->anyOfs);
        }

        if (\count($this->allOfs) > 0) {
            $jsonSchema['allOf'] = \array_map(function(SchemaMetadata $schema) {
                return $schema->toJsonSchema();
            }, $this->allOfs);
        }

        return $jsonSchema;
    }
}
