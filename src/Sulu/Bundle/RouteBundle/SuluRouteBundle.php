<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle;

use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Bundle\RouteBundle\DependencyInjection\RouteGeneratorCompilerPass;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Symfony\CompilerPass\TaggedServiceCollectorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Entry point of sulu-route-bundle.
 */
class SuluRouteBundle extends Bundle
{
    use PersistenceBundleTrait;

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RouteGeneratorCompilerPass());
        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass('sulu_route.routing.defaults_provider', 'sulu_route.defaults_provider')
        );
        $this->buildPersistence([RouteInterface::class => 'sulu.model.route.class'], $container);
    }
}
