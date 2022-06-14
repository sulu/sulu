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
use Sulu\Bundle\PersistenceBundle\Doctrine\Types\EncryptArray;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluPersistenceBundle extends Bundle
{
    public function boot(): void
    {
        if ($this->container->hasParameter('sulu_persistence.encryption_key')) {
            // TODO find a better way to inject the encryption key: https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/events.html#postconnect-event
            EncryptArray::setEncryptionKey($this->container->getParameter('sulu_persistence.encryption_key'));
        }
    }

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
