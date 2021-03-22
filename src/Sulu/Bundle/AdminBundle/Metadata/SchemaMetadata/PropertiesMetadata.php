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

class PropertiesMetadata implements SchemaMetadataInterface
{
    /**
     * @var PropertyMetadata[]
     */
    private $properties;

    /**
     * @param PropertyMetadata[] $properties
     */
    public function __construct(array $properties)
    {
        $this->properties = $properties;
    }

    public function toJsonSchema(): array
    {
        $jsonSchema = [];
        $properties = [];

        foreach ($this->properties as $property) {
            $jsonSchemaProperty = $property->toJsonSchema();
            if (!$jsonSchemaProperty) {
                continue;
            }

            $properties[$property->getName()] = $jsonSchemaProperty;
        }

        if (!empty($properties)) {
            $jsonSchema['properties'] = $properties;
        }

        $required = \array_values(
            \array_map(
                function (PropertyMetadata $propertyMetadata) {
                    return $propertyMetadata->getName();
                },
                \array_filter(
                    $this->properties,
                    function (PropertyMetadata $propertyMetadata) {
                        return $propertyMetadata->isMandatory();
                    }
                )
            )
        );

        if (!empty($required)) {
            $jsonSchema['required'] = $required;
        }

        return $jsonSchema;
    }
}
