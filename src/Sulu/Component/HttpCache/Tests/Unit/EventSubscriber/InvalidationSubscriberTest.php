<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\Tests\Unit\EventListener;

use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\HttpCache\EventSubscriber\InvalidationSubscriber;
use Sulu\Component\HttpCache\HandlerInvalidateStructureInterface;

class InvalidationSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HandlerInvalidateStructureInterface
     */
    private $handler;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var InvalidationSubscriber
     */
    private $invalidationSubscriber;

    public function setUp()
    {
        $this->handler = $this->prophesize(HandlerInvalidateStructureInterface::class);
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);

        $this->invalidationSubscriber = new InvalidationSubscriber(
            $this->handler->reveal(),
            $this->structureManager->reveal(),
            $this->documentInspector->reveal()
        );
    }

    public function testInvalidateDocumentForPublishing()
    {
        $event = $this->prophesize(PublishEvent::class);
        $document = $this->prophesize(StructureBehavior::class);
        $metadata = $this->prophesize(Metadata::class);
        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $structureBridge = $this->prophesize(StructureBridge::class);

        $metadata->getAlias()->willReturn('page');
        $event->getDocument()->willReturn($document->reveal());

        $this->documentInspector->getMetadata($document->reveal())->willReturn($metadata->reveal());
        $this->documentInspector->getStructureMetadata($document->reveal())->willReturn($structureMetadata->reveal());

        $this->structureManager->wrapStructure(
            'page',
            $structureMetadata->reveal()
        )->willReturn($structureBridge->reveal());

        $structureBridge->setDocument($document->reveal())->shouldBeCalled();
        $this->handler->invalidateStructure($structureBridge->reveal())->shouldBeCalled();

        $this->invalidationSubscriber->invalidateDocumentForPublishing($event->reveal());
    }

    public function testInvalidateDocumentForPublishingWithWrongDocument()
    {
        $event = $this->prophesize(PublishEvent::class);
        $document = new \stdClass();

        $event->getDocument()->willReturn($document);

        $this->handler->invalidateStructure(Argument::cetera())->shouldNotBeCalled();

        $this->invalidationSubscriber->invalidateDocumentForPublishing($event->reveal());
    }
}
