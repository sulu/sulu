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

use JMS\Serializer\Construction\DoctrineObjectConstructor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class JmsObjectConstructorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (null === $container->getDefinition('jms_serializer.doctrine_object_constructor')->getDecoratedService()) {
            return;
        }

        $container->removeDefinition('jms_serializer.object_constructor');
        $container->setAlias('jms_serializer.doctrine_object_constructor', DoctrineObjectConstructor::class);
        $container->setAlias('jms_serializer.object_constructor', 'jms_serializer.unserialize_object_constructor');
    }
}
