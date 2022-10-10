<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\Mapping;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\MixinSubscriber;

class MixinSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MetadataFactoryInterface>
     */
    private $metadataFactory;

    /**
     * @var MixinSubscriber
     */
    private $mixinSubscriber;

    public function setUp(): void
    {
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->mixinSubscriber = new MixinSubscriber($this->metadataFactory->reveal());
    }

    public function testSetDocumentMixinsOnNode(): void
    {
        $event = $this->prophesize(AbstractMappingEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $node->hasProperty('jcr:uuid')->willReturn(false);
        $metadata = $this->prophesize(Metadata::class);
        $metadata->getPhpcrType()->willReturn('phpcr:type');
        $document = new \stdClass();

        $this->metadataFactory->getMetadataForClass(\get_class($document))->willReturn($metadata->reveal());

        $event->getNode()->willReturn($node->reveal());
        $event->getDocument()->willReturn($document);

        $node->addMixin('phpcr:type')->shouldBeCalled();
        $node->setProperty('jcr:uuid', Argument::type('string'))->shouldBeCalled();

        $this->mixinSubscriber->setDocumentMixinsOnNode($event->reveal());
    }

    public function testSetDocumentMixinsOnNodeWithDocumentUuid(): void
    {
        $event = $this->prophesize(AbstractMappingEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $node->hasProperty('jcr:uuid')->willReturn(false);
        $metadata = $this->prophesize(Metadata::class);
        $metadata->getPhpcrType()->willReturn('phpcr:type');
        $document = $this->prophesize(UuidBehavior::class);
        $document->getUuid()->willReturn('document-uuid-1234');

        $this->metadataFactory->getMetadataForClass(\get_class($document->reveal()))->willReturn($metadata->reveal());

        $event->getNode()->willReturn($node->reveal());
        $event->getDocument()->willReturn($document->reveal());

        $node->addMixin('phpcr:type')->shouldBeCalled();
        $node->setProperty('jcr:uuid', 'document-uuid-1234')->shouldBeCalled();

        $this->mixinSubscriber->setDocumentMixinsOnNode($event->reveal());
    }

    public function testSetDocumentMixinsOnNodeWithUuidProperty(): void
    {
        $event = $this->prophesize(AbstractMappingEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $node->hasProperty('jcr:uuid')->willReturn(true);
        $node->getMixinNodeTypes()->willReturn([]);
        $metadata = $this->prophesize(Metadata::class);
        $metadata->getPhpcrType()->willReturn('phpcr:type');
        $document = new \stdClass();

        $this->metadataFactory->getMetadataForClass(\get_class($document))->willReturn($metadata->reveal());

        $event->getNode()->willReturn($node->reveal());
        $event->getDocument()->willReturn($document);

        $node->removeMixin(Argument::cetera())->shouldNotBeCalled();
        $node->addMixin('phpcr:type')->shouldBeCalled();
        $node->setProperty('jcr:uuid', Argument::type('string'))->shouldNotBeCalled();

        $this->mixinSubscriber->setDocumentMixinsOnNode($event->reveal());
    }
}
