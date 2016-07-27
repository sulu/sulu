<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\Compiler\ResolveTargetEntitiesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds a `ResolveTargetEntitiesPass` for the defined interfaces
 * which will resolve the target entities automatically.
 */
trait PersistenceBundleTrait
{
    /**
     * Build persistence adds a `ResolveTargetEntitiesPass` for the given interfaces.
     *
     * @param array            $interfaces Target entities resolver configuration.
     *                                     Mapping interfaces to a concrete implementation
     * @param ContainerBuilder $container
     */
    public function buildPersistence(array $interfaces, ContainerBuilder $container)
    {
        if (!empty($interfaces)) {
            $container->addCompilerPass(
                new ResolveTargetEntitiesPass($interfaces)
            );
        }
    }
}
