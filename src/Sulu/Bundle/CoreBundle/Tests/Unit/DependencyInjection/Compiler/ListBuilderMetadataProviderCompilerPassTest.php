<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Tests\Unit\DependencyInjection\Compiler;

use Prophecy\Argument;
use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\ListBuilderMetadataProviderCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ListBuilderMetadataProviderCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessProvider()
    {
        return [
            [false],
            [true],
            [true, ['id1' => []]],
            [true, ['id1' => [], 'id2' => []]],
        ];
    }

    /**
     * @dataProvider testProcessProvider
     */
    public function testProcess($hasDefinition, $taggedServices = [])
    {
        $definition = $this->prophesize(Definition::class);

        $container = $this->prophesize(ContainerBuilder::class);
        $container->hasDefinition(ListBuilderMetadataProviderCompilerPass::CHAIN_PROVIDER_ID)
            ->willReturn($hasDefinition);

        if ($hasDefinition) {
            $container->getDefinition(ListBuilderMetadataProviderCompilerPass::CHAIN_PROVIDER_ID)
                ->shouldBeCalled()->willReturn($definition);

            $container->findTaggedServiceIds(ListBuilderMetadataProviderCompilerPass::PROVIDER_TAG_ID)
                ->shouldBeCalled()->willReturn($taggedServices);

            $definition->replaceArgument(
                0,
                Argument::that(
                    function (array $argument) use ($taggedServices) {
                        foreach ($argument as $item) {
                            if (!$item instanceof Reference
                                || !in_array(
                                    $item->__toString(),
                                    array_keys($taggedServices),
                                    true
                                )
                            ) {
                                return false;
                            }
                        }

                        return true;
                    }
                )
            )->shouldBeCalled();
        }

        $compilerPass = new ListBuilderMetadataProviderCompilerPass();

        $compilerPass->process($container->reveal());
    }
}
