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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentManagerRegistry;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class DocumentManagerRegistryTest extends \PHPUnit_Framework_TestCase
{
    private $registry;
    private $container;
    private $manager;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->registry = new DocumentManagerRegistry(
            $this->container->reveal(),
            array(
                'default' => 'document_manager.1.id',
                'staging' => 'document_manager.2.id',
                'live' => 'document_manager.3.id',
            ),
            'default'
        );

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
    public function testGetDefaultManager()
    {
        $this->container->get('document_manager.1.id')->willReturn($this->manager->reveal());
        $manager = $this->registry->getManager();
        $this->assertSame(
            $this->manager->reveal(),
            $manager
        );
    }

    /**
     * It should return the named document manager.
     */
    public function testNamedDocumentManager()
    {
        $this->container->get('document_manager.2.id')->willReturn($this->manager->reveal());
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

