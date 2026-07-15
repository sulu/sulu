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

    /**
     * @return array<string, mixed>
     */
    public function toJsonSchema(): array
    {
        return $this->buildJsonSchema(false);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildJsonSchema(bool $includeType): array
    {
        $properties = [];
        $required = [];

        /** @var array<string, PropertyMetadata[]> $nestedProperties */
        $nestedProperties = [];

        foreach ($this->properties as $property) {
            $name = $property->getName();

            // a name with a slash (e.g. "attributes/1") describes a nested property; the first segment is grouped
            // and the rest is handled recursively, so it ends up as a nested object in the JSON schema
            if (\str_contains($name, '/')) {
                [$parent, $child] = \explode('/', $name, 2);
                $nestedProperties[$parent][] = new PropertyMetadata(
                    $child,
                    $property->isMandatory(),
                    $property->getSchemaMetadata()
                );

                continue;
            }

            $propertySchema = $property->toJsonSchema();
            if ($propertySchema) {
                $properties[$name] = $propertySchema;
            }

            if ($property->isMandatory()) {
                $required[$name] = true;
            }
        }

        foreach ($nestedProperties as $parent => $childProperties) {
            $properties[$parent] = (new self($childProperties))->buildJsonSchema(true);
        }

        return $this->assembleJsonSchema($properties, $required, $includeType);
    }

    /**
     * @param array<int|string, mixed> $properties
     * @param array<int|string, bool> $required
     *
     * @return array<string, mixed>
     */
    private function assembleJsonSchema(array $properties, array $required, bool $includeType): array
    {
        $jsonSchema = [];

        if ($includeType && (!empty($properties) || !empty($required))) {
            $jsonSchema['type'] = 'object';
        }

        if (!empty($properties)) {
            // cast to object because PHP coerces numeric property names (e.g. "0", "1") to a list, which json_encode
            // would serialize as a JSON array although the "properties" keyword of a JSON schema requires an object
            $jsonSchema['properties'] = \array_is_list($properties) ? (object) $properties : $properties;
        }

        if (!empty($required)) {
            // cast to string because PHP coerces numeric property names to integer array keys, which would produce
            // an invalid JSON schema (the "required" keyword only allows strings)
            $jsonSchema['required'] = \array_map('\strval', \array_keys($required));
        }

        return $jsonSchema;
    }
}
