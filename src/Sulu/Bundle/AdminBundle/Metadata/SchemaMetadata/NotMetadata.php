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

class NotMetadata implements SchemaMetadataInterface
{
    /**
     * @var SchemaMetadataInterface
     */
    private $schema;

    public function __construct(SchemaMetadataInterface $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @return array<string, mixed>
     */
    public function toJsonSchema(): array
    {
        return [
            'not' => $this->schema->toJsonSchema(),
        ];
    }
}
