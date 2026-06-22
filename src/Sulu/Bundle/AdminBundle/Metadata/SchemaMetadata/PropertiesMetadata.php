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
        return $this->buildJsonSchema($this->createPropertyTree(), false);
    }

    /**
     * @return array<string, array{children: array, mandatory: bool, schema: array|null}>
     */
    private function createPropertyTree(): array
    {
        $tree = [];

        foreach ($this->properties as $property) {
            $pathSegments = \explode('/', $property->getName());
            $siblings = &$tree;

            foreach ($pathSegments as $pathSegment) {
                if (!\array_key_exists($pathSegment, $siblings)) {
                    $siblings[$pathSegment] = [
                        'children' => [],
                        'mandatory' => false,
                        'schema' => null,
                    ];
                }

                $node = &$siblings[$pathSegment];
                $siblings = &$node['children'];
            }

            $node['mandatory'] = $property->isMandatory();
            $node['schema'] = $property->toJsonSchema();

            unset($node, $siblings);
        }

        return $tree;
    }

    /**
     * @param array<string, array{children: array, mandatory: bool, schema: array|null}> $propertyTree
     */
    private function buildJsonSchema(array $propertyTree, bool $includeType): array
    {
        $jsonSchema = [];
        $properties = [];
        $required = [];

        foreach ($propertyTree as $propertyName => $propertyNode) {
            $propertySchema = $propertyNode['schema'];

            if (!empty($propertyNode['children'])) {
                $propertySchema = \array_merge(
                    $propertySchema ?? [],
                    $this->buildJsonSchema($propertyNode['children'], true)
                );
            }

            if ($propertySchema) {
                $properties[$propertyName] = $propertySchema;
            }

            if ($propertyNode['mandatory']) {
                // cast to string because PHP coerces numeric property names (e.g. "1") to integer array keys,
                // which would produce an invalid JSON schema (the "required" keyword only allows strings)
                $required[] = (string) $propertyName;
            }
        }

        if ($includeType && (!empty($properties) || !empty($required))) {
            $jsonSchema['type'] = 'object';
        }

        if (!empty($properties)) {
            $jsonSchema['properties'] = $properties;
        }

        if (!empty($required)) {
            $jsonSchema['required'] = $required;
        }

        return $jsonSchema;
    }
}
