<?php

/*
 * This file is part of Sulu.
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

/**
 * Collects all url provider and combines them in the chain-provider.
 */
class WebspaceUrlProviderCompilerPass implements CompilerPassInterface
{
    const SERVICE_ID = 'sulu_core.webspace.url_provider';
    const TAG_NAME = 'sulu.webspace.url_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_ID)) {
            return;
        }

        $references = [];
        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $id => $tags) {
            $references[] = new Reference($id);
        }

        $container->getDefinition(self::SERVICE_ID)->replaceArgument(0, $references);
    }
}
