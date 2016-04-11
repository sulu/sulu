<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractContainerBuilderTestCase;
use Sulu\Bundle\PersistenceBundle\Tests\Unit\Fixture\DependencyInjection\UsingPersistenceExtensionTrait;

class PersistenceExtensionTraitTest extends AbstractContainerBuilderTestCase
{
    public function testPersistenceExtensionTrait()
    {
        $extension = new UsingPersistenceExtensionTrait();
        $extension->load(
            [
                'objects' => [
                    'foo' => [
                        'model' => 'Sulu\Component\Persistence\Model\Foo',
                    ],
                    'bar' => [
                        'model' => 'Sulu\Component\Persistence\Model\Bar',
                        'repository' => 'Sulu\Bundle\PersistenceBundle\Entity\BarRepository',
                    ],
                ],
            ],
            $this->container
        );

        $this->assertContainerBuilderHasParameter(
            'sulu.model.foo.class',
            'Sulu\Component\Persistence\Model\Foo'
        );

        $this->assertContainerBuilderHasParameter(
            'sulu.model.bar.class',
            'Sulu\Component\Persistence\Model\Bar'
        );

        $this->assertContainerBuilderHasParameter(
            'sulu.repository.bar.class',
            'Sulu\Bundle\PersistenceBundle\Entity\BarRepository'
        );

        $this->assertContainerBuilderHasParameter(
            'sulu.persistence.objects',
            [
                'sulu' => [
                    'foo' => [
                        'model' => 'Sulu\Component\Persistence\Model\Foo',
                    ],
                    'bar' => [
                        'model' => 'Sulu\Component\Persistence\Model\Bar',
                        'repository' => 'Sulu\Bundle\PersistenceBundle\Entity\BarRepository',
                    ],
                ],
            ]
        );

        $this->assertContainerBuilderHasService(
            'sulu.repository.bar',
            'Sulu\Bundle\PersistenceBundle\Entity\BarRepository'
        );
    }
}
