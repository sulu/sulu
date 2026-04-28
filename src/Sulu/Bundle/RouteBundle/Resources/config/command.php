<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Sulu\Bundle\RouteBundle\Command\MovePageTreeCommand;
use Sulu\Bundle\RouteBundle\Command\UpdateRouteCommand;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_route.command.update_route', UpdateRouteCommand::class)
        ->args([
            new Reference('translator'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_route.manager.route_manager'),
        ])
        ->tag('console.command');

    $services->set('sulu_route.move_page_tree_command', MovePageTreeCommand::class)
        ->args([
            new Reference('sulu_route.page_tree_route.mover'),
            new Reference('sulu.content.resource_locator.strategy_pool'),
            new Reference('sulu_document_manager.document_manager'),
        ])
        ->tag('console.command');
};
