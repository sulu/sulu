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

class ConstMetadata implements SchemaMetadataInterface
{
    /**
     * @var string|number|null
     */
    private $value;

    /**
     * @param string|number|null $value
     */
    public function __construct($value = null)
    {
        $this->value = $value;
    }

    public function toJsonSchema(): array
    {
        return [
            'const' => $this->value,
        ];
    }
}
