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

class StringMetadata extends PropertyMetadata
{
    public function __construct(string $name, bool $mandatory)
    {
        parent::__construct($name, $mandatory, 'string');
    }
}
