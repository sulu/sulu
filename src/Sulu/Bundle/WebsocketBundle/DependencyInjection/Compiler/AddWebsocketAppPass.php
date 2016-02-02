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
 * Adds all websocket apps (with tag 'sulu.websocket.app') to manager.
 */
class AddWebsocketAppPass implements CompilerPassInterface
{
    /**
     * Service id of websocket manager.
     */
    const MANAGER_ID = 'sulu_websocket.manager';

    /**
     * Tag name for websocket apps.
     */
    const APP_TAG = 'sulu.websocket.app';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $manager = $container->getDefinition(self::MANAGER_ID);

        $taggedServices = $container->findTaggedServiceIds(self::APP_TAG);

        foreach ($taggedServices as $id => $tags) {
            $app = new Reference($id);

            foreach ($tags as $attributes) {
                $route = $attributes['route'];
                $httpHost = array_key_exists('httpHost', $attributes) ? $attributes['httpHost'] : null;
                $allowedOrigins = array_key_exists(
                    'allowedOrigins',
                    $attributes
                ) ? $attributes['allowedOrigins'] : ['*'];

                $manager->addMethodCall('add', [$route, $app, $allowedOrigins, $httpHost]);
            }
        }
    }
}
