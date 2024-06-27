<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle\Tests\Unit\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\Compiler\ResolveTargetEntitiesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

interface FooInterface
{
}
interface BarInterface
{
}

class ResolveTargetEntitiesPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(
            new ResolveTargetEntitiesPass(
                [
                    FooInterface::class => 'sulu.model.foo.class',
                    BarInterface::class => '\stdClass',
                ]
            )
        );

        $this->setParameter('sulu.model.foo.class', '\stdClass');
        $this->setDefinition(
            'doctrine.orm.listeners.resolve_target_entity',
            new Definition()
        );
    }

    public function testResolveTargetEntities(): void
    {
        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'doctrine.orm.listeners.resolve_target_entity',
            'addResolveTargetEntity',
            [
                FooInterface::class,
                '\stdClass',
                [],
            ]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'doctrine.orm.listeners.resolve_target_entity',
            'addResolveTargetEntity',
            [
                BarInterface::class,
                '\stdClass',
                [],
            ]
        );
    }
}
