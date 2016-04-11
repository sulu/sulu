<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Sulu\Bundle\MediaBundle\DependencyInjection\ImageCommandCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Test the image command compiler pass.
 */
class ImageCommandCompilerPassTest extends AbstractCompilerPassTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ImageCommandCompilerPass());
    }

    /**
     * @test
     */
    public function if_compiler_pass_collects_services_by_adding_method_calls_these_will_exist()
    {
        $commandManager = new Definition();
        $this->setDefinition('sulu_media.image.command_manager', $commandManager);

        $command = new Definition();
        $command->addTag('sulu_media.image.command', ['alias' => 'resize']);
        $this->setDefinition('sulu_media.image.command.resize', $command);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sulu_media.image.command_manager',
            'add',
            [
                new Reference('sulu_media.image.command.resize'),
                'resize',
            ]
        );
    }
}
