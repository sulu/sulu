<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle\DependencyInjection\Compiler;

use Sulu\Component\Persistence\Migrations\MigrationFactoryDecorator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
class DecorateMigrationFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // doctrine-migrations-bundle is optional; nothing to decorate if it isn't installed
        if (!$container->hasDefinition('doctrine.migrations.migrations_factory')) {
            return;
        }

        $definition = new Definition(MigrationFactoryDecorator::class, [
            new Reference('.inner'),
            '%sulu.persistence.legacy_length%',
        ]);
        $definition->setDecoratedService('doctrine.migrations.migrations_factory');

        $container->setDefinition(MigrationFactoryDecorator::class, $definition);
    }
}
