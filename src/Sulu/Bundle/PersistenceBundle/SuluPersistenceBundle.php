<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\Compiler\ActivateResolveTargetEntityResolverPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluPersistenceBundle extends Bundle
{
    /**
     * @return void
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new ActivateResolveTargetEntityResolverPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            10 // need to be run before the "EntityListenerPass" of the "DoctrineBundle"
        );
    }
}
