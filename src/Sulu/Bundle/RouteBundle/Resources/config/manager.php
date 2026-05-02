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

use Sulu\Bundle\RouteBundle\Manager\AutoIncrementConflictResolver;
use Sulu\Bundle\RouteBundle\Manager\RouteManager;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_route.manager.conflict_resolver.auto_increment', AutoIncrementConflictResolver::class)
        ->args([new Reference('sulu.repository.route')]);

    $services->set('sulu_route.manager.route_manager', RouteManager::class)
        ->args([
            new Reference('sulu_route.chain_generator'),
            new Reference('sulu_route.manager.conflict_resolver.auto_increment'),
            new Reference('sulu.repository.route'),
        ]);

    $services->alias(RouteManagerInterface::class, 'sulu_route.manager.route_manager');
};
