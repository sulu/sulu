<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\DependencyInjection\Compiler;

use Sulu\Bundle\DocumentManagerBundle\DependencyInjection\Compiler\InitializerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class InitializerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testPass()
    {
        $container = new ContainerBuilder();

        $initializer = $container->register('sulu_document_manager.initializer', 'Sulu\Bundle\DocumentManagerBundle\Initializer\Initializer');
        $initializer->addArgument('one');
        $initializer->addArgument('two');
        $initializer->addArgument('three');

        $initializer1 = $container->register('my_initializer_1', '\Foo');
        $initializer1->addTag('sulu_document_manager.initializer', ['priority' => 500]);
        $initializer2 = $container->register('my_initializer_2', '\Bar');
        $initializer2->addTag('sulu_document_manager.initializer', ['priority' => -500]);

        $pass = new InitializerPass();
        $pass->process($container);

        $map = $initializer->getArgument(1);

        $this->assertCount(2, $map);
        $this->assertEquals([
            'my_initializer_1' => 500,
            'my_initializer_2' => -500,
        ], $map);
    }
}
