<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Compat;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\Structure\SnippetBridge;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Compat\StructureManager;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Extension\ExtensionInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\StructureMetadata;

class StructureManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<StructureMetadataFactory>
     */
    private $factory;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $inspector;

    /**
     * @var ObjectProphecy<StructureMetadata>
     */
    private $structure;

    /**
     * @var ObjectProphecy<ExtensionInterface>
     */
    private $extension;

    /**
     * @var ObjectProphecy<LegacyPropertyFactory>
     */
    private $propertyFactory;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    public function setUp(): void
    {
        $this->factory = $this->prophesize(StructureMetadataFactory::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->structure = $this->prophesize(StructureMetadata::class);
        $this->extension = $this->prophesize(ExtensionInterface::class);
        $this->propertyFactory = $this->prophesize(LegacyPropertyFactory::class);

        $typemap = [
            'page' => '\\' . PageBridge::class,
            'home' => '\\' . PageBridge::class,
            'snippet' => '\\' . SnippetBridge::class,
        ];

        $this->structureManager = new StructureManager(
            $this->factory->reveal(),
            $this->inspector->reveal(),
            $this->propertyFactory->reveal(),
            $typemap
        );
    }

    /**
     * It should get a legacy structure bridge.
     */
    public function testGetStructure(): void
    {
        $structureType = 'content';
        $documentType = 'page';
        $this->factory->getStructureMetadata($documentType, $structureType)->willReturn($this->structure->reveal());
        $bridge = $this->structureManager->getStructure($structureType, $documentType);

        $this->assertInstanceOf(StructureBridge::class, $bridge);
    }
}
