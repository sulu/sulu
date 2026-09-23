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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\AnyOfsMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NullMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ObjectMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;

/**
 * A number range is `{from, to}` with both bounds set, or null unless the field is required. Each
 * bound is validated like a number field with the same params; that "from" does not exceed "to"
 * cannot be expressed in a JSON schema.
 *
 * @internal use symfony dependency injection container to override the service if you want to change the behavior
 */
final readonly class NumberRangePropertyMetadataMapper implements PropertyMetadataMapperInterface
{
    public function __construct(
        private NumberPropertyMetadataMapper $numberPropertyMetadataMapper,
    ) {
    }

    public function mapPropertyMetadata(FieldMetadata $fieldMetadata): PropertyMetadata
    {
        $range = new ObjectMetadata([
            $this->mapBound($fieldMetadata, 'from'),
            $this->mapBound($fieldMetadata, 'to'),
        ]);

        $mandatory = $fieldMetadata->isRequired();

        return new PropertyMetadata(
            $fieldMetadata->getName(),
            $mandatory,
            $mandatory ? $range : new AnyOfsMetadata([new NullMetadata(), $range]),
        );
    }

    private function mapBound(FieldMetadata $fieldMetadata, string $name): PropertyMetadata
    {
        $bound = new FieldMetadata($name);
        $bound->setRequired(true);

        foreach ($fieldMetadata->getOptions() as $option) {
            $bound->addOption($option);
        }

        return $this->numberPropertyMetadataMapper->mapPropertyMetadata($bound);
    }
}
