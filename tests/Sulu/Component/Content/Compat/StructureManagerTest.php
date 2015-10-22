<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Extension\ExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManager;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\StructureMetadata;

class StructureManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StructureMetadataFactory
     */
    private $factory;

    /**
     * @var ExtensionManager
     */
    private $extensionManager;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var StructureMetadata
     */
    private $structure;

    /**
     * @var ExtensionInterface
     */
    private $extension;

    /**
     * @var LegacyPropertyFactory
     */
    private $propertyFactory;

    public function setUp()
    {
        $this->factory = $this->prophesize(StructureMetadataFactory::class);
        $this->extensionManager = $this->prophesize(ExtensionManager::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->structure = $this->prophesize(StructureMetadata::class);
        $this->extension = $this->prophesize(ExtensionInterface::class);
        $this->propertyFactory = $this->prophesize(LegacyPropertyFactory::class);

        $typemap = [
            'page' => '\Sulu\Component\Content\Compat\Structure\PageBridge',
            'home' => '\Sulu\Component\Content\Compat\Structure\PageBridge',
            'snippet' => '\Sulu\Component\Content\Compat\Structure\SnippetBridge',
        ];

        $this->structureManager = new StructureManager(
            $this->factory->reveal(),
            $this->extensionManager->reveal(),
            $this->inspector->reveal(),
            $this->propertyFactory->reveal(),
            $typemap
        );
    }

    /**
     * It should get a legacy structure bridge.
     */
    public function testGetStructure()
    {
        $structureType = 'content';
        $documentType = 'page';
        $this->factory->getStructureMetadata($documentType, $structureType)->willReturn($this->structure->reveal());
        $bridge = $this->structureManager->getStructure($structureType, $documentType);

        $this->assertInstanceOf(StructureBridge::class, $bridge);
    }

    /**
     * It should add extensions.
     */
    public function testAddExtension()
    {
        $this->extensionManager->addExtension($this->extension->reveal(), 'content')->shouldBeCalled();
        $this->structureManager->addExtension($this->extension->reveal(), 'content');
    }

    /**
     * It should return extensions.
     */
    public function testGetExtensions()
    {
        $this->extensionManager->getExtensions('content')->willReturn([
            $this->extension->reveal(),
        ]);
        $extensions = $this->structureManager->getExtensions('content');
        $this->assertCount(1, $extensions);
    }

    /**
     * It can say if it has an extension.
     */
    public function testHasExtension()
    {
        $this->extensionManager->hasExtension('content', 'foo')->willReturn(true);
        $result = $this->structureManager->hasExtension('content', 'foo');
        $this->assertTrue($result);
    }

    /**
     * It should get an extension.
     */
    public function testGetExtension()
    {
        $this->extensionManager->getExtension('content', 'foo')->willReturn($this->extension->reveal());
        $extension = $this->structureManager->getExtension('content', 'foo');
        $this->assertSame($this->extension->reveal(), $extension);
    }
}
