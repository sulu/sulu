<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle\Tests\Unit;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractContainerBuilderTestCase;
use Sulu\Bundle\PersistenceBundle\Tests\Unit\Fixture\Bundle\UsingPersistenceBundleTrait;
use Symfony\Component\DependencyInjection\Definition;

class PersistenceBundleTraitTest extends AbstractContainerBuilderTestCase
{
    public function testModelInterfaceMapping(): void
    {
        $bundle = new UsingPersistenceBundleTrait();
        $bundle->modelInterfaces = [
            'Sulu\Component\Persistence\Model\BarInterface' => '\stdClass',
        ];
        $bundle->build($this->container);

        $this->setDefinition(
            'doctrine.orm.listeners.resolve_target_entity',
            new Definition()
        );

        $this->compile();

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

    public function testModelInterfaceMappingWithParameter(): void
    {
        $bundle = new UsingPersistenceBundleTrait();
        $bundle->modelInterfaces = [
            'Sulu\Component\Persistence\Model\FooInterface' => 'sulu.model.foo.class',
        ];
        $bundle->build($this->container);

        $this->setParameter('sulu.model.foo.class', '\stdClass');
        $this->setDefinition(
            'doctrine.orm.listeners.resolve_target_entity',
            new Definition()
        );

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
    }

    public function testInvalidModelInterfaceMapping(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $bundle = new UsingPersistenceBundleTrait();
        $bundle->modelInterfaces = [
            'Sulu\Component\Persistence\Model\FooInterface' => 'sulu.model.foo.class',
        ];
        $bundle->build($this->container);

        $this->container->setDefinition(
            'doctrine.orm.listeners.resolve_target_entity',
            new Definition()
        );

        $this->compile();
    }
}
