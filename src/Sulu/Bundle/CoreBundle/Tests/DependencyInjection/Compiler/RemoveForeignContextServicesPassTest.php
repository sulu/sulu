<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RemoveForeignContextServicesPassTest extends AbstractCompilerPassTestCase
{
    public function provideWebsiteServices()
    {
        return [
            [
                [
                    ['service' => 'service 1', 'context' => 'website', 'included' => true],
                    ['service' => 'service 2', 'context' => 'website', 'included' => true],
                    ['service' => 'service 3', 'context' => 'admin', 'included' => false],
                ],
            ],
        ];
    }

    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RemoveForeignContextServicesPass());
    }

    /**
     * @dataProvider provideWebsiteServices
     */
    public function testRemoveWebsiteServices($services)
    {
        $this->setParameter('sulu.context', 'website');

        foreach ($services as $service) {
            $definition = new Definition();
            $definition->addTag('sulu.context', ['context' => $service['context']]);

            $this->setDefinition($service['service'], $definition);
        }

        $this->compile();

        foreach ($services as $service) {
            if ($service['included']) {
                $this->assertContainerBuilderHasService($service['service']);
            } else {
                $this->assertContainerBuilderNotHasService($service['service']);
            }
        }
    }
}
