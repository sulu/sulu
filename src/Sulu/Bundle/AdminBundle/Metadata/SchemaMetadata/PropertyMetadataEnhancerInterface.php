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

interface PropertyMetadataEnhancerInterface
{
    public function enhancePropertyMetadata(PropertyMetadata $propertyMetadata, ItemMetadata $itemMetadata): void;
}
