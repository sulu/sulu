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
use Sulu\Bundle\LocationBundle\DependencyInjection\Compiler\GeolocatorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class GeolocatorPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new GeolocatorPass());
    }

    public function testRegisterGeolocators()
    {
        $managerDef = new Definition();
        $this->setDefinition('sulu_location.geolocator.manager', $managerDef);

        $geolocatorDef = new Definition();
        $geolocatorDef->addTag('sulu_location.geolocator', ['alias' => 'my_alias']);
        $this->setDefinition('geolocator_id', $geolocatorDef);
        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sulu_location.geolocator.manager',
            'register',
            [
                'my_alias',
                'geolocator_id',
            ]
        );
    }
}
