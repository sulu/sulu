<?php

namespace Sulu\Component\Content\Compat;

use Sulu\Component\Content\Structure\Factory\StructureFactory;
use Sulu\Component\Content\Extension\ExtensionManager;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Structure\Structure;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Extension\ExtensionInterface;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;

class StructureManagerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->factory = $this->prophesize(StructureFactory::class);
        $this->extensionManager = $this->prophesize(ExtensionManager::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->structure = $this->prophesize(Structure::class);
        $this->extension = $this->prophesize(ExtensionInterface::class);
        $this->propertyFactory = $this->prophesize(LegacyPropertyFactory::class);

        $this->structureManager = new StructureManager(
            $this->factory->reveal(),
            $this->extensionManager->reveal(),
            $this->inspector->reveal(),
            $this->propertyFactory->reveal()
        );
    }

    /**
     * It should get a legacy structure bridge
     */
    public function testGetStructure()
    {
        $structureType = 'content';
        $documentType = 'page';
        $this->factory->getStructure($documentType, $structureType)->willReturn($this->structure->reveal());
        $bridge = $this->structureManager->getStructure($structureType, $documentType);

        $this->assertInstanceOf(StructureBridge::class, $bridge);
    }

    /**
     * It should add extensions
     */
    public function testAddExtension()
    {
        $this->extensionManager->addExtension($this->extension->reveal(), 'content')->shouldBeCalled();
        $this->structureManager->addExtension($this->extension->reveal(), 'content');
    }

    /**
     * It should return extensions
     */
    public function testGetExtensions()
    {
        $this->extensionManager->getExtensions('content')->willReturn(array(
            $this->extension->reveal()
        ));
        $extensions = $this->structureManager->getExtensions('content');
        $this->assertCount(1, $extensions);
    }

    /**
     * It can say if it has an extension
     */
    public function testHasExtension()
    {
        $this->extensionManager->hasExtension('content', 'foo')->willReturn(true);
        $result = $this->structureManager->hasExtension('content', 'foo');
        $this->assertTrue($result);
    }

    /**
     * It should get an extension
     */
    public function testGetExtension()
    {
        $this->extensionManager->getExtension('content', 'foo')->willReturn($this->extension->reveal());
        $extension = $this->structureManager->getExtension('content', 'foo');
        $this->assertSame($this->extension->reveal(), $extension);
    }
}
