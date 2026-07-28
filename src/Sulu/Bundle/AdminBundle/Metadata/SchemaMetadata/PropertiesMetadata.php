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

            // a slash in the name (e.g. "settings/title") denotes a nested object
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
            $nestedSchema = (new self($childProperties))->buildJsonSchema(true);
            if ($nestedSchema) {
                $properties[$parent] = $nestedSchema;
            }
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
            // numeric names (e.g. "0", "1") form a PHP list, which json_encode would emit as a JSON array
            $jsonSchema['properties'] = \array_is_list($properties) ? (object) $properties : $properties;
        }

        if (!empty($required)) {
            // PHP coerces numeric names to int keys, but "required" only allows strings
            $jsonSchema['required'] = \array_map('\strval', \array_keys($required));
        }

        return $jsonSchema;
    }
}
