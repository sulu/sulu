<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection\Compiler;

use Sulu\Component\Rest\ExceptionSerializerHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExceptionHandlerCompilerPass implements CompilerPassInterface
{
    const SERVICE_ID = 'fos_rest.serializer.exception_normalizer.jms';

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_ID)) {
            return;
        }

        $definition = $container->getDefinition(self::SERVICE_ID);
        $definition->setClass(ExceptionSerializerHandler::class);
        $definition->addArgument('%kernel.environment%');
    }
}
