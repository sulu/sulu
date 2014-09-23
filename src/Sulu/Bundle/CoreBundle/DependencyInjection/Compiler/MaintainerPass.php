<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MaintainerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sulu.maintainence_manager')) {
            return;
        }

        $maintainenceManager = $container->getDefinition('sulu.maintainence_manager');

        $ids = $container->findTaggedServiceIds('sulu.maintainer');
        foreach (array_keys($ids) as $id) {
            $maintainenceManager->addMethodCall(
                'registerMaintainer',
                array(new Reference($id))
            );
        }
    }
}
