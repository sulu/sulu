<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;

class OnlyKeysVisistor implements TypedFormMetadataVisitorInterface
{
    public function visitTypedFormMetadata(
        TypedFormMetadata $formMetadata,
        string $key,
        string $locale,
        array $metadataOptions = []
    ): void {
        $onlyKeys = $metadataOptions['onlyKeys'] ?? false;
        if (!$onlyKeys) {
            return;
        }

        foreach ($formMetadata->getForms() as $metaData) {
            $metaData->setItems([]);
            $metaData->setSchema(new SchemaMetadata([], [], []));
        }
    }
}
