<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use Symfony\Component\DependencyInjection\ContainerInterface;

trait ContainerTrait
{
    /**
     * @internal The "getContainer" function is deprecated use self::$container variable instead.
     *
     * This function exist to keep a compatibility between symfony versions.
     * Can be removed when Symfony <4.1 is not longer supported.
     */
    protected static function getContainer(): ContainerInterface
    {
        if (!static::$container) {
            static::bootKernel();
        }

        return static::$container;
    }
}
