<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Bridge;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentManagerRegistry;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sulu\Component\DocumentManager\DocumentManagerContext;

class DocumentManagerRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentManagerRegistry
     */
    private $registry;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var DocumentManagerContext
     */
    private $context;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->registry = new DocumentManagerRegistry(
            $this->container->reveal(),
            [
                'default' => 'document_context.1.id',
                'staging' => 'document_context.2.id',
                'live' => 'document_context.3.id',
            ],
            'default'
        );

        $this->context = $this->prophesize(DocumentManagerContext::class);
        $this->manager = $this->prophesize(DocumentManagerInterface::class);
    }

    /**
     * It should return the default document manager name.
     */
    public function testGetDefaultName()
    {
        $name = $this->registry->getDefaultManagerName();
        $this->assertEquals('default', $name);
    }

    /**
     * It should return the default document managre if no argument given.
     */
    public function testGetDefaultContext()
    {
        $this->container->get('document_context.1.id')->willReturn($this->context->reveal());
        $context = $this->registry->getContext();
        $this->assertSame(
            $this->context->reveal(),
            $context
        );
    }

    /**
     * It should return the named document context.
     */
    public function testNamedDocumentContext()
    {
        $this->container->get('document_context.2.id')->willReturn($this->context->reveal());
        $context = $this->registry->getContext('staging');
        $this->assertSame(
            $this->context->reveal(),
            $context
        );
    }

    /**
     * It should return the named document manager
     */
    public function testGetNamedDocumentManager()
    {
        $this->container->get('document_context.2.id')->willReturn($this->context->reveal());
        $this->context->getManager()->willReturn($this->manager->reveal());
        $manager = $this->registry->getManager('staging');
        $this->assertSame(
            $this->manager->reveal(),
            $manager
        );

    }

    /**
     * It should throw an exception if the named document manager does not exist.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Manager with name "foo_foo" does not exist.
     */
    public function testNotExist()
    {
        $this->registry->getManager('foo_foo');
    }
}
