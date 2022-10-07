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
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Component\Content\Compat\Serializer\PageBridgeSubscriber;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Metadata\StructureMetadata;

class PageBridgeSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public function testSerialize(): void
    {
        $pageBridge = $this->prophesize(PageBridge::class);
        $document = $this->prophesize(PageDocument::class);
        $structure = $this->prophesize(StructureMetadata::class);

        $pageBridge->getStructure()->willReturn($structure->reveal());
        $pageBridge->getDocument()->willReturn($document->reveal());

        $structure->getName()->willReturn('test');

        $node = $this->prophesize(NodeInterface::class);
        $node->getIdentifier()->willReturn('123-123-123');

        $pageBridgeSubscriber = new PageBridgeSubscriber();

        $visitor = $this->prophesize(SerializationVisitorInterface::class);
        $context = $this->prophesize(Context::class);
        $context->getVisitor()->willReturn($visitor->reveal());
        $graphNavigator = $this->prophesize(GraphNavigatorInterface::class);
        $context->getNavigator()->willReturn($graphNavigator->reveal());

        $event = new ObjectEvent(
            $context->reveal(),
            $pageBridge->reveal(),
            ['type' => PageBridge::class, 'params' => []]
        );

        $pageBridgeSubscriber->onPostSerialize($event);

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'document' === $metadata->name;
        }), $document->reveal())->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'documentClass' === $metadata->name;
        }), \get_class($document->reveal()))->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'structure' === $metadata->name;
        }), 'test')->shouldBeCalled();
    }
}
