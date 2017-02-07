<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber\Compat;

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Compat\StructureManager;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Subscriber\Compat\ContentMapperSubscriber;
use Sulu\Component\Content\Mapper\ContentEvents;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Mapper\Event\ContentNodeDeleteEvent;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\Util\SuluNodeHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContentMapperSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var SuluNodeHelper
     */
    private $nodeHelper;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var ContentMapperSubscriber
     */
    private $contentMapperSubscriber;

    public function setUp()
    {
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->contentMapper = $this->prophesize(ContentMapperInterface::class);
        $this->nodeHelper = $this->prophesize(SuluNodeHelper::class);
        $this->structureManager = $this->prophesize(StructureManager::class);

        $this->contentMapperSubscriber = new ContentMapperSubscriber(
            $this->documentInspector->reveal(),
            $this->eventDispatcher->reveal(),
            $this->contentMapper->reveal(),
            $this->nodeHelper->reveal(),
            $this->structureManager->reveal()
        );
    }

    public function testHandlePreRemove()
    {
        $document = $this->prophesize(StructureBehavior::class);
        $node = $this->prophesize(NodeInterface::class);
        $this->documentInspector->getNode($document)->willReturn($node->reveal());
        $this->documentInspector->getWebspace($document)->willReturn(null);

        $this->eventDispatcher
            ->dispatch(ContentEvents::NODE_PRE_DELETE, Argument::type(ContentNodeDeleteEvent::class))
            ->shouldBeCalled();
        $this->contentMapperSubscriber->handlePreRemove(new RemoveEvent($document->reveal()));

        $this->eventDispatcher
            ->dispatch(ContentEvents::NODE_POST_DELETE, Argument::type(ContentNodeDeleteEvent::class))
            ->shouldBeCalled();
        $this->contentMapperSubscriber->handlePostRemove(new RemoveEvent($document->reveal()));
    }

    public function testPersistAndFlush()
    {
        $document1 = $this->prophesize(StructureBehavior::class);
        $node1 = $this->prophesize(NodeInterface::class);
        $metadata1 = $this->prophesize(Metadata::class);
        $metadata1->getAlias()->willReturn('page');
        $structureMetadata1 = $this->prophesize(StructureMetadata::class);
        $structureBridge1 = $this->prophesize(StructureBridge::class);

        $document2 = $this->prophesize(StructureBehavior::class);
        $node2 = $this->prophesize(NodeInterface::class);
        $metadata2 = $this->prophesize(Metadata::class);
        $metadata2->getAlias()->willReturn('home');
        $structureMetadata2 = $this->prophesize(StructureMetadata::class);
        $structureBridge2 = $this->prophesize(StructureBridge::class);

        $this->documentInspector->getStructureMetadata($document1->reveal())->willReturn($structureMetadata1->reveal());
        $this->documentInspector->getStructureMetadata($document2->reveal())->willReturn($structureMetadata2->reveal());
        $this->documentInspector->getMetadata($document1->reveal())->willReturn($metadata1->reveal());
        $this->documentInspector->getMetadata($document2->reveal())->willReturn($metadata2->reveal());
        $this->documentInspector->getNode($document1)->willReturn($node1);
        $this->documentInspector->getNode($document2)->willReturn($node2);

        $this->structureManager->wrapStructure('page', $structureMetadata1->reveal())->willReturn($structureBridge1);
        $structureBridge1->setDocument($document1->reveal());
        $this->structureManager->wrapStructure('home', $structureMetadata2->reveal())->willReturn($structureBridge2);
        $structureBridge2->setDocument($document2->reveal());

        $this->contentMapperSubscriber->handlePersist(new PersistEvent($document1->reveal(), 'de'));
        $this->contentMapperSubscriber->handlePersist(new PersistEvent($document2->reveal(), 'de'));

        $this->eventDispatcher->dispatch(ContentEvents::NODE_POST_SAVE, Argument::any())->shouldBeCalled();
        $this->eventDispatcher->dispatch(ContentEvents::NODE_POST_SAVE, Argument::any())->shouldBeCalled();

        $this->contentMapperSubscriber->handleFlush(new FlushEvent());
    }
}
