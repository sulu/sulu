<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Compat\Serializer;

use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\VisitorInterface;
use Metadata\MetadataFactoryInterface;
use PHPCR\NodeInterface;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Serializer\PageBridgeHandler;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\StructureMetadata;

class PageBridgeHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialize()
    {
        $documentInspector = $this->prophesize(DocumentInspector::class);
        $propertyFactory = $this->prophesize(LegacyPropertyFactory::class);
        $structureFactory = $this->prophesize(StructureMetadataFactory::class);

        $pageBridge = $this->prophesize(PageBridge::class);
        $document = $this->prophesize(PageDocument::class);
        $structure = $this->prophesize(StructureMetadata::class);

        $pageBridge->getStructure()->willReturn($structure->reveal());
        $pageBridge->getDocument()->willReturn($document->reveal());

        $structure->getName()->willReturn('test');

        $node = $this->prophesize(NodeInterface::class);
        $node->getIdentifier()->willReturn('123-123-123');

        $pageBridgeHandler = new PageBridgeHandler(
            $documentInspector->reveal(),
            $propertyFactory->reveal(),
            $structureFactory->reveal()
        );

        $visitor = $this->prophesize(VisitorInterface::class);
        $context = $this->prophesize(Context::class);

        $pageBridgeHandler->doSerialize($visitor->reveal(), $pageBridge->reveal(), [], $context->reveal());

        $context->accept(
            [
                'document' => $document->reveal(),
                'documentClass' => get_class($document->reveal()),
                'structure' => 'test',
            ]
        )->shouldBeCalledTimes(1);
    }

    public function testDeserialize()
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

        $visitor = $this->prophesize(VisitorInterface::class);
        $context = $this->prophesize(Context::class);

        $context->accept('serialized document', ['name' => PageDocument::class])->willReturn($document->reveal());
        $structureFactory->getStructureMetadata('page', 'test')->willReturn($structureMetadata->reveal());

        $navigator = new GraphNavigator(
            $this->prophesize(MetadataFactoryInterface::class)->reveal(),
            $this->prophesize(HandlerRegistryInterface::class)->reveal(),
            $this->prophesize(ObjectConstructorInterface::class)->reveal()
        );
        $context->getNavigator()->willReturn($navigator);
        $visitor->setNavigator($navigator)->willReturn(null);

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

        $context->accept('serialized document', ['name' => PageDocument::class])->shouldBeCalledTimes(1);
    }
}
