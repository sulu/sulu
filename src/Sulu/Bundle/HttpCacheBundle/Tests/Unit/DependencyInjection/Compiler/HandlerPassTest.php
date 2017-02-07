<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class HandlerPassTest extends AbstractCompilerPassTestCase
{
    public function provideHandlerServices()
    {
        return [
            [
                [
                    ['service' => 'service1', 'alias' => 'foo'],
                    ['service' => 'service2', 'alias' => 'bar'],
                    ['service' => 'service3', 'alias' => 'baz'],
                ],
                [
                    'foo', 'bar', 'baz',
                ],
                [
                    'service1', 'service2', 'service3',
                ],
                'Sulu\Component\HttpCache\HandlerInterface',
            ],
            [
                [
                    ['service' => 'service1', 'alias' => 'foo'],
                    ['service' => 'service2', 'alias' => 'bar'],
                    ['service' => 'service3', 'alias' => 'baz'],
                ],
                [
                    'foo', 'baz',
                ],
                [
                    'service1', 'service3',
                ],
                'Sulu\Component\HttpCache\HandlerInterface',
            ],
            [
                [
                    ['service' => 'service3', 'alias' => 'ball'],
                ],
                [
                    'foo', 'baz',
                ],
                [
                ],
                'Sulu\Component\HttpCache\HandlerInterface',
                'Could not find the following cache handlers: "foo", "baz"',
            ],
            [
                [
                    ['service' => 'service3', 'alias' => 'ball'],
                    ['service' => 'service4', 'alias' => 'ball'],
                ],
                [
                ],
                [
                ],
                'Sulu\Component\HttpCache\HandlerInterface',
                'Cache handler with alias "ball" has already been registered',
            ],
            [
                [
                ],
                [
                ],
                [
                ],
                'Sulu\Component\HttpCache\HandlerInterface',
            ],
            [
                [
                    ['service' => 'service3', 'alias' => 'ball'],
                ],
                [
                ],
                [
                ],
                'stdClass',
                'Service ID "service3" was tagged as a cache handler, but it does not implement the "HandlerInterface"',
            ],
        ];
    }

    /**
     * @dataProvider provideHandlerServices
     */
    public function testHandlerPass($services, $handlerAliases, $expectedHandlerIds, $handlerClass, $exception = null)
    {
        if ($exception) {
            $this->setExpectedException('InvalidArgumentException', $exception);
        }

        foreach ($services as $service) {
            $definition = new Definition($this->getMock($handlerClass));
            $definition->addTag('sulu_http_cache.handler', ['alias' => $service['alias']]);
            $this->setDefinition($service['service'], $definition);
        }

        $aggregateHandler = new Definition();
        $aggregateHandler->addArgument(null);
        $this->setDefinition('sulu_http_cache.handler.aggregate', $aggregateHandler);
        $this->setParameter('sulu_http_cache.handler.aggregate.handlers', $handlerAliases);
        $this->compile();

        $res = $this->container->getDefinition('sulu_http_cache.handler.aggregate');
        $args = $res->getArguments();

        foreach ($expectedHandlerIds as $expectedHandlerId) {
            $reference = array_shift($args[0]);
            $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $reference);
            $this->assertEquals($expectedHandlerId, (string) $reference);
        }
    }

    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new HandlerPass());
    }
}
