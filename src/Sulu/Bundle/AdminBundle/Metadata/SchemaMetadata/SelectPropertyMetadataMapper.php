<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata;

use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;

class SelectPropertyMetadataMapper implements PropertyMetadataMapperInterface
{
    public function mapPropertyMetadata(ContentPropertyMetadata $propertyMetadata): PropertyMetadata
    {
        $mandatory = $propertyMetadata->isRequired();

        $selectMetadata = new AnyOfsMetadata([
            new StringMetadata(),
            new NumberMetadata(),
        ]);

        if (!$mandatory) {
            $selectMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                $selectMetadata,
            ]);
        }

        return new PropertyMetadata($propertyMetadata->getName(), $mandatory, $selectMetadata);
    }
}
