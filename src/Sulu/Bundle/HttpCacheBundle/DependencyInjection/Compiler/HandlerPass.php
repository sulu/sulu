<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Add tagged cache handler definitions to the aggregate cache handler
 */
class HandlerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sulu_http_cache.handler.aggregate')) {
            return;
        }

        $aggregateHandler = $container->getDefinition('sulu_http_cache.handler.aggregate');
        $taggedHandlers = $container->findTaggedServiceIds('sulu_http_cache.handler');

        $knownHandlers = array();

        foreach ($taggedHandlers as $id => $attributes) {
            if (!isset($attributes[0]['alias'])) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'No "alias" specified for cache handler service ID: "%s"',
                        $id
                    )
                );
            }

            $alias = $attributes[0]['alias'];

            if (isset($knownHandlers[$alias])) {
                throw new \InvalidArgumentException(sprintf(
                    'Cache handler with alias "%s" has already been registered',
                    $alias
                ));
            }
            $knownHandlers[$alias] = $id;
        }

        $handlers = array();
        $configuredAliases = $container->getParameter('sulu_http_cache.handler.aggregate.handlers');

        foreach ($configuredAliases as $configuredAlias) {
            if (isset($knownHandlers[$configuredAlias])) {
                $handlers[] = new Reference($knownHandlers[$configuredAlias]);
            }
        }

        if (count($handlers) !== count($configuredAliases)) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find the following cache handlers: "%s"',
                implode('", "', array_diff($configuredAliases, array_keys($handlers)))
            ));
        }

        $aggregateHandler->replaceArgument(0, array_values($handlers));
    }
}
