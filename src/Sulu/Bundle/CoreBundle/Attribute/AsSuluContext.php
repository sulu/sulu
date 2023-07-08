<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AsSuluContext
{
    public function __construct(public string $context)
    {
    }
}
