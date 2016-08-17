<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Unit\DependencyInjection;

use Prophecy\Argument;
use Sulu\Bundle\RouteBundle\DependencyInjection\RouteGeneratorCompilerPass;
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RouteGeneratorCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $serviceId = 'sulu_route.route_generator.by_schema';

        $compilerPass = new RouteGeneratorCompilerPass();

        $container = $this->prophesize(ContainerBuilder::class);
        $container->hasDefinition(RouteGeneratorCompilerPass::SERVICE_ID)->willReturn(true);
        $container->hasParameter(RouteGeneratorCompilerPass::PARAMETER_NAME)->willReturn(true);
        $container->getParameter(RouteGeneratorCompilerPass::PARAMETER_NAME)->willReturn(
            [
                \stdClass::class => [
                    'service_id' => $serviceId,
                    'options' => [
                        'route_schema' => '/{entity.getTitle()}',
                    ],
                ],
            ]
        );

        $optionsResolver = $this->prophesize(OptionsResolver::class);
        $optionsResolver->resolve(['route_schema' => '/{entity.getTitle()}'])->shouldBeCalled();

        $generator = $this->prophesize(RouteGeneratorInterface::class);
        $generator->getOptionsResolver(['route_schema' => '/{entity.getTitle()}'])
            ->willReturn($optionsResolver->reveal());

        $container->get($serviceId)->willReturn($generator->reveal());

        $definition = $this->prophesize(Definition::class);
        $definition->replaceArgument(
            0,
            Argument::that(
                function ($argument) use ($serviceId) {
                    return 1 === count($argument) && $argument[$serviceId]->__toString() === $serviceId;
                }
            )
        )->shouldBeCalled();

        $container->getDefinition(RouteGeneratorCompilerPass::SERVICE_ID)->willReturn($definition->reveal());

        $compilerPass->process($container->reveal());
    }

    public function testProcessNoService()
    {
        $serviceId = 'sulu_route.route_generator.by_schema';

        $compilerPass = new RouteGeneratorCompilerPass();

        $container = $this->prophesize(ContainerBuilder::class);
        $container->hasDefinition(RouteGeneratorCompilerPass::SERVICE_ID)->willReturn(false);
        $container->get($serviceId)->shouldNotBeCalled();

        $compilerPass->process($container->reveal());
    }

    public function testProcessNoParameter()
    {
        $serviceId = 'sulu_route.route_generator.by_schema';

        $compilerPass = new RouteGeneratorCompilerPass();

        $container = $this->prophesize(ContainerBuilder::class);
        $container->hasDefinition(RouteGeneratorCompilerPass::SERVICE_ID)->willReturn(true);
        $container->hasParameter(RouteGeneratorCompilerPass::PARAMETER_NAME)->willReturn(false);
        $container->get($serviceId)->shouldNotBeCalled();

        $compilerPass->process($container->reveal());
    }

    public function testProcessEmptyConfig()
    {
        $serviceId = 'sulu_route.route_generator.by_schema';

        $compilerPass = new RouteGeneratorCompilerPass();

        $container = $this->prophesize(ContainerBuilder::class);
        $container->hasDefinition(RouteGeneratorCompilerPass::SERVICE_ID)->willReturn(true);
        $container->hasParameter(RouteGeneratorCompilerPass::PARAMETER_NAME)->willReturn(true);
        $container->getParameter(RouteGeneratorCompilerPass::PARAMETER_NAME)->willReturn([]);

        $container->get($serviceId)->shouldNotBeCalled();

        $definition = $this->prophesize(Definition::class);
        $definition->replaceArgument(
            0,
            Argument::that(
                function ($argument) use ($serviceId) {
                    return 0 === count($argument);
                }
            )
        )->shouldBeCalled();

        $container->getDefinition(RouteGeneratorCompilerPass::SERVICE_ID)->willReturn($definition->reveal());

        $compilerPass->process($container->reveal());
    }
}
