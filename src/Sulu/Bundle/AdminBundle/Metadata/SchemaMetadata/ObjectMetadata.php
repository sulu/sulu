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

class ObjectMetadata extends PropertyMetadata
{
    /**
     * @var array<string, mixed>|null
     */
    private $jsonSchema;

    public function __construct(string $name, bool $mandatory, ?array $jsonSchema = null)
    {
        $this->jsonSchema = $jsonSchema;

        parent::__construct($name, $mandatory);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function toJsonSchema(): ?array
    {
        return $this->jsonSchema;
    }
}
