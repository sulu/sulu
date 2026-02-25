<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal no backwards compatibility promise is given for this class it can be removed at any time
 */
class SuluHttpCacheCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('http_cache') && $container->getDefinition('http_cache')->isPublic()) {
            throw new \InvalidArgumentException('Enabling the Symfony Http Cache is not compatible with Sulu! Cache is handled in index.php!');
        }
    }
}
