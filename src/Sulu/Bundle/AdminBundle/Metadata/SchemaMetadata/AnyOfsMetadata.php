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

class AnyOfsMetadata implements SchemaMetadataInterface
{
    /**
     * @var SchemaMetadataInterface[]
     */
    private $anyOfs;

    /**
     * @param SchemaMetadataInterface[] $anyOfs
     */
    public function __construct(array $anyOfs)
    {
        $this->anyOfs = $anyOfs;
    }

    public function toJsonSchema(): array
    {
        if (!empty($this->anyOfs)) {
            return [
                'anyOf' => \array_map(function (SchemaMetadataInterface $schema) {
                    return $schema->toJsonSchema();
                }, $this->anyOfs),
            ];
        }

        return [];
    }
}
