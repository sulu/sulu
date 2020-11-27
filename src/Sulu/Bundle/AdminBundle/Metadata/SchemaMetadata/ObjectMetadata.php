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
     * @param PropertyMetadata[] $properties
     */
    public function __construct(string $name, bool $mandatory, array $properties = [])
    {
        parent::__construct($name, $mandatory, 'object');

        $this->properties = $properties;
    }

    public function toJsonSchema(): ?array
    {
        $jsonSchema = [
            'name' => $this->getName(),
            'type' => $this->getType(),
        ];

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

        return $jsonSchema;
    }
}
