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

class ObjectMetadata implements SchemaMetadataInterface
{
    /**
     * @var PropertiesMetadata
     */
    private $propertiesMetadata;

    /**
     * @var int|null
     */
    private $minProperties;

    /**
     * @var int|null
     */
    private $maxProperties;

    /**
     * @param PropertyMetadata[] $properties
     */
    public function __construct(array $properties = [], ?int $minProperties = null, ?int $maxProperties = null)
    {
        $this->propertiesMetadata = new PropertiesMetadata($properties);
        $this->minProperties = $minProperties;
        $this->maxProperties = $maxProperties;
    }

    public function toJsonSchema(): array
    {
        $jsonSchema = \array_merge(
            [
                'type' => 'object',
            ],
            $this->propertiesMetadata->toJsonSchema()
        );

        if (null !== $this->minProperties) {
            $jsonSchema['minProperties'] = $this->minProperties;
        }

        if (null !== $this->maxProperties) {
            $jsonSchema['maxProperties'] = $this->maxProperties;
        }

        return $jsonSchema;
    }
}
