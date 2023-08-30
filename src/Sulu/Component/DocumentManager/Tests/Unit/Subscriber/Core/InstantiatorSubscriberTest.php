<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit\Subscriber\Core;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Event\CreateEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\Subscriber\Core\InstantiatorSubscriber;

class InstantiatorSubscriberTest extends TestCase
{
    use ProphecyTrait;

    private const ALIAS = 'alias';

    /**
     * @var ObjectProphecy<MetadataFactoryInterface>
     */
    private $metadataFactory;

    /**
     * @var ObjectProphecy<Metadata>
     */
    private $metadata;

    /**
     * @var ObjectProphecy<HydrateEvent>
     */
    private $hydrateEvent;
    /**
     * @var ObjectProphecy<CreateEvent>
     */
    private $createEvent;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var InstantiatorSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->subscriber = new InstantiatorSubscriber(
            $this->metadataFactory->reveal()
        );

        $this->metadata = $this->prophesize(Metadata::class);
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->createEvent = $this->prophesize(CreateEvent::class);
        $this->node = $this->prophesize(NodeInterface::class);
    }

    /**
     * It should create a document for a managed PHPCR node.
     */
    public function testHandleHydrate(): void
    {
        $this->hydrateEvent->hasDocument()->willReturn(false);
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->metadataFactory->getMetadataForPhpcrNode($this->node->reveal())->willReturn(
            $this->metadata->reveal()
        );
        $this->metadata->getClass()->willReturn('\stdClass');
        $this->hydrateEvent->setDocument(Argument::type('stdClass'))->shouldBeCalled();

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * If the document has already been set, do nothing.
     */
    public function testHandleHydrateDocumentAlreadySet(): void
    {
        $this->hydrateEvent->hasDocument()->willReturn(true)->shouldBeCalled();
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should create a new document.
     */
    public function testHandleCreate(): void
    {
        $this->createEvent->getAlias()->willReturn(self::ALIAS);
        $this->metadataFactory->getMetadataForAlias(self::ALIAS)->willReturn(
            $this->metadata->reveal()
        );
        $this->metadata->getClass()->willReturn('\stdClass');
        $this->createEvent->setDocument(Argument::type('stdClass'))->shouldBeCalled();

        $this->subscriber->handleCreate($this->createEvent->reveal());
    }
}
