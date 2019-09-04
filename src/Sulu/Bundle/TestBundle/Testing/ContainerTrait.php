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
        if (static::$container) {
            return static::$container;
        }

        if (!self::$kernel) {
            // Boot kernel if container is not yet available to keep backward compatibility for tests.
            static::bootKernel();

            if (static::$container) {
                return static::$container;
            }
        }

        // Get the container from kernel to keep compatibility for symfony <4.1
        $container = static::$kernel->getContainer();

        if (!$container) {
            // If the kernel was not booted the container is empty so we need to boot it
            static::bootKernel();

            $container = static::$kernel->getContainer();

            if (!$container) {
                throw new \RuntimeException('Could not boot kernel with container for test.');
            }
        }

        static::$container = $container;

        return static::$container;
    }

    protected static function bootKernel(array $options = [])
    {
        $kernel = parent::bootKernel($options);

        if (!static::$container) {
            // In symfony <4.1 container is not set to a static::$container this is here to keep compatibility
            static::$container = static::$kernel->getContainer();
        }

        return $kernel;
    }

    protected static function ensureKernelShutdown()
    {
        parent::ensureKernelShutdown();
        // In symfony <4.1 container is not set to null on ensureKernelShutdown this keep compatibility
        static::$container = null;
    }
}
