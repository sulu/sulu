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

class ConstMetadata extends PropertyMetadata
{
    /**
     * @var string|number|null
     */
    private $value;

    /**
     * @param string|number|null $value
     */
    public function __construct(string $name, bool $mandatory, $value = null)
    {
        parent::__construct($name, $mandatory);
        $this->value = $value;
    }

    public function toJsonSchema(): ?array
    {
        return [
            'name' => $this->getName(),
            'const' => $this->value,
        ];
    }
}
