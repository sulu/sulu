<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SchemaMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\AllOfsMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ArrayMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ConstMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\IfThenElseMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\RefSchemaMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;

/**
 * @internal use symfony dependency injection container to override the service if you want to change the behavior
 */
final readonly class BlockPropertyMetadataMapper implements PropertyMetadataMapperInterface
{
    public function __construct(
        private SchemaMetadataProvider $schemaMetadataProvider,
    ) {
    }

    public function mapPropertyMetadata(FieldMetadata $fieldMetadata): PropertyMetadata
    {
        $blockTypeSchemas = [];
        foreach ($fieldMetadata->getTypes() as $blockType) {
            $tag = $blockType->findTag('sulu.global_block');
            if ($tag instanceof TagMetadata) {
                $blockName = $tag->getAttributes()['global_block'] ?? null;
                \assert(\is_string($blockName), 'Global block name is expected to be defined and a string.');

                $blockTypeSchemas[] = new IfThenElseMetadata(
                    new SchemaMetadata([
                        new PropertyMetadata('type', true, new ConstMetadata($blockType->getKey())),
                    ]),
                    new RefSchemaMetadata('#/definitions/' . $blockName)
                );

                continue;
            }

            $blockTypeSchemas[] = new IfThenElseMetadata(
                new SchemaMetadata([
                    new PropertyMetadata('type', true, new ConstMetadata($blockType->getKey())),
                ]),
                $this->schemaMetadataProvider->getMetadata($blockType->getItems()),
            );
        }

        return new PropertyMetadata(
            $fieldMetadata->getName(),
            $fieldMetadata->isRequired(),
            new ArrayMetadata(
                new AllOfsMetadata($blockTypeSchemas)
            )
        );
    }
}
