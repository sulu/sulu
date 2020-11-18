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

    public function supports(ItemMetadata $itemMetadata): bool
    {
        throw new \Exception('This method should never be called and is most likely a bug.');
    }

    public function enhancePropertyMetadata(PropertyMetadata $propertyMetadata, ItemMetadata $itemMetadata): PropertyMetadata
    {
        /** @var PropertyMetadataEnhancerInterface $propertyMetadataEnhancer */
        foreach ($this->propertyMetadataEnhancers as $propertyMetadataEnhancer) {
            if ($this === $propertyMetadataEnhancer) {
                continue;
            }

            if (!$propertyMetadataEnhancer->supports($itemMetadata)) {
                continue;
            }

            $propertyMetadata = $propertyMetadataEnhancer->enhancePropertyMetadata($propertyMetadata, $itemMetadata);
        }

        return $propertyMetadata;
    }
}
