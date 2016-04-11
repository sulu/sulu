<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Tests\Unit\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\Compiler\ResolveTargetEntitiesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ResolveTargetEntitiesPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new ResolveTargetEntitiesPass(
                [
                    'Sulu\Component\Persistence\Model\FooInterface' => 'sulu.model.foo.class',
                    'Sulu\Component\Persistence\Model\BarInterface' => '\stdClass',
                ]
            )
        );

        $this->setParameter('sulu.model.foo.class', '\stdClass');
        $this->setDefinition(
            'doctrine.orm.listeners.resolve_target_entity',
            new Definition()
        );
    }

    public function testResolveTargetEntities()
    {
        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'doctrine.orm.listeners.resolve_target_entity',
            'addResolveTargetEntity',
            [
                'Sulu\Component\Persistence\Model\FooInterface',
                '\stdClass',
                [],
            ]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'doctrine.orm.listeners.resolve_target_entity',
            'addResolveTargetEntity',
            [
                'Sulu\Component\Persistence\Model\BarInterface',
                '\stdClass',
                [],
            ]
        );
    }
}
