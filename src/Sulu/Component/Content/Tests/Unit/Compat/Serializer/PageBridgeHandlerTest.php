<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Compat\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Component\Content\Compat\Serializer\PageBridgeHandler;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\StructureMetadata;

class PageBridgeHandlerTest extends TestCase
{
    use ProphecyTrait;

    public function testDeserialize(): void
    {
        $documentInspector = $this->prophesize(DocumentInspector::class);
        $propertyFactory = $this->prophesize(LegacyPropertyFactory::class);
        $structureFactory = $this->prophesize(StructureMetadataFactory::class);

        $document = $this->prophesize(PageDocument::class);
        $structureMetadata = $this->prophesize(StructureMetadata::class);

        $structure = $this->prophesize(StructureInterface::class);
        $document->getStructure()->willReturn($structure->reveal());

        $pageBridgeHandler = new PageBridgeHandler(
            $documentInspector->reveal(),
            $propertyFactory->reveal(),
            $structureFactory->reveal()
        );

        $visitor = $this->prophesize(DeserializationVisitorInterface::class);
        $context = $this->prophesize(Context::class);
        $graphNavigator = $this->prophesize(GraphNavigatorInterface::class);

        $graphNavigator->accept('serialized document', ['name' => PageDocument::class])->willReturn($document->reveal());
        $structureFactory->getStructureMetadata('page', 'test')->willReturn($structureMetadata->reveal());

        $graphNavigator = $this->prophesize(GraphNavigatorInterface::class);
        $context->getNavigator()->willReturn($graphNavigator->reveal());

        $pageBridgeHandler->doDeserialize(
            $visitor->reveal(),
            [
                'document' => 'serialized document',
                'documentClass' => PageDocument::class,
                'structure' => 'test',
            ],
            ['name' => PageBridge::class],
            $context->reveal()
        );

        $graphNavigator->accept('serialized document', ['name' => PageDocument::class])->shouldBeCalledTimes(1);
    }
}
