<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle;

use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Bundle\SecurityBundle\DependencyInjection\Compiler\CurrentUserDataCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluSecurityBundle extends Bundle
{
    use PersistenceBundleTrait;

    public function build(ContainerBuilder $container)
    {
        $this->buildPersistence(
            [
                'Sulu\Component\Security\Authentication\UserInterface' => 'sulu.model.user.class',
                'Sulu\Component\Security\Authentication\RoleInterface' => 'sulu.model.role.class',
            ],
            $container
        );

        $container->addCompilerPass(new CurrentUserDataCompilerPass());

        parent::build($container);
    }
}
