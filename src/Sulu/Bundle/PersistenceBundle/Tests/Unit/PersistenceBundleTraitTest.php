<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle\Tests\Unit;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractContainerBuilderTestCase;
use Sulu\Bundle\PersistenceBundle\Tests\Unit\Fixture\Bundle\UsingPersistenceBundleTrait;
use Symfony\Component\DependencyInjection\Definition;

class PersistenceBundleTraitTest extends AbstractContainerBuilderTestCase
{
    public function testModelInterfaceMapping()
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

    public function testModelInterfaceMappingWithParameter()
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

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidModelInterfaceMapping()
    {
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
