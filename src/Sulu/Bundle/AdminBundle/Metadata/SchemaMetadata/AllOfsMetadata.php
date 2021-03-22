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

class AllOfsMetadata implements SchemaMetadataInterface
{
    /**
     * @var SchemaMetadataInterface[]
     */
    private $allOfs;

    /**
     * @param SchemaMetadataInterface[] $allOfs
     */
    public function __construct(array $allOfs)
    {
        $this->allOfs = $allOfs;
    }

    public function toJsonSchema(): array
    {
        if (!empty($this->allOfs)) {
            return [
                'allOf' => \array_map(function (SchemaMetadataInterface $schema) {
                    return $schema->toJsonSchema();
                }, $this->allOfs),
            ];
        }

        return [];
    }
}
