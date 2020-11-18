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

use Sulu\Component\Content\Metadata\ItemMetadata;

class PropertyMetadataEnhancer implements PropertyMetadataEnhancerInterface
{
    /**
     * @var iterable<PropertyMetadataEnhancerInterface>
     */
    private $propertyMetadataEnhancers;

    /**
     * @param iterable<PropertyMetadataEnhancerInterface> $propertyMetadataEnhancers
     */
    public function __construct(iterable $propertyMetadataEnhancers)
    {
        $this->propertyMetadataEnhancers = $propertyMetadataEnhancers;
    }

    public function enhancePropertyMetadata(PropertyMetadata $propertyMetadata, ItemMetadata $itemMetadata): void
    {
        /** @var PropertyMetadataEnhancerInterface $propertyMetadataEnhancer */
        foreach ($this->propertyMetadataEnhancers as $propertyMetadataEnhancer) {
            if ($this === $propertyMetadataEnhancer) {
                continue;
            }

            $propertyMetadataEnhancer->enhancePropertyMetadata($propertyMetadata, $itemMetadata);
        }
    }
}
