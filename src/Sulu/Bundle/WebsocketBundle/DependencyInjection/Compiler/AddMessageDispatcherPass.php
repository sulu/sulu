<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsocketBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Combines all message dispatcher and handler.
 */
class AddMessageDispatcherPass implements CompilerPassInterface
{
    /**
     * Service id of websocket manager.
     */
    const DISPATCHER_TAG = 'sulu.websocket.message.dispatcher';

    /**
     * Tag name for websocket apps.
     */
    const HANDLER_TAG = 'sulu.websocket.message.handler';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $dispatchers = $this->findDispatchers($container);
        $taggedHandlers = $container->findTaggedServiceIds(self::HANDLER_TAG);

        foreach ($taggedHandlers as $id => $tags) {
            $handler = new Reference($id);

            foreach ($tags as $attributes) {
                $dispatcherName = $attributes['dispatcher'];
                $alias = $attributes['alias'];

                if (array_key_exists($dispatcherName, $dispatchers)) {
                    $dispatchers[$dispatcherName]->addMethodCall('add', [$alias, $handler]);
                }
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return Reference[]
     */
    private function findDispatchers(ContainerBuilder $container)
    {
        $dispatchers = [];
        $taggedDispatchers = $container->findTaggedServiceIds(self::DISPATCHER_TAG);

        foreach ($taggedDispatchers as $id => $tags) {
            $dispatcher = $container->getDefinition($id);

            foreach ($tags as $attributes) {
                $alias = $attributes['alias'];

                $dispatchers[$alias] = $dispatcher;
            }
        }

        return $dispatchers;
    }
}
