<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\StructureTypeFilingBehavior;
use Sulu\Component\Content\Document\Subscriber\StructureTypeFilingSubscriber;
use Sulu\Component\DocumentManager\Behavior\Path\BasePathBehavior;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;

class StructureTypeFilingSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PersistEvent
     */
    private $persistEvent;

    /**
     * @var BasePathBehavior
     */
    private $document;

    /**
     * @var \stdClass
     */
    private $parentDocument;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var MetaData
     */
    private $metadata;

    /**
     * @var NodeInterface
     */
    private $parentNode;

    /**
     * @var SessionInterface
     */
    private $defaultSession;

    /**
     * @var NodeInterface
     */
    private $defaultNode;

    /**
     * @var SessionInterface
     */
    private $liveSession;

    /**
     * @var NodeInterface
     */
    private $liveNode;

    /**
     * @var StructureTypeFilingSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->document = $this->prophesize(StructureTypeFilingBehavior::class);
        $this->document->willImplement(StructureBehavior::class);
        $this->parentDocument = new \stdClass();
        $this->documentManager = $this->prophesize(DocumentManager::class);
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->defaultSession = $this->prophesize(SessionInterface::class);
        $this->defaultNode = $this->prophesize(NodeInterface::class);
        $this->liveSession = $this->prophesize(SessionInterface::class);
        $this->liveNode = $this->prophesize(NodeInterface::class);

        $this->defaultSession->getRootNode()->willReturn($this->defaultNode->reveal());
        $this->liveSession->getRootNode()->willReturn($this->liveNode->reveal());

        $this->subscriber = new StructureTypeFilingSubscriber(
            $this->defaultSession->reveal(),
            $this->liveSession->reveal()
        );
    }

    /**
     * It should return early if the document is not implementing the behavior.
     */
    public function testPersistNotImplementing()
    {
        $this->persistEvent->getDocument()->willReturn(new \stdClass());
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should set the parent document.
     */
    public function testSetParentDocument()
    {
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->hasParentNode()->willReturn(true);
        $this->persistEvent->getParentNode()->willReturn($this->parentNode->reveal());
        $this->parentNode->getPath()->willReturn('/cmf');
        $this->document->getStructureType()->willReturn('banner');

        $defaultCmfNode = $this->prophesize(NodeInterface::class);
        $this->defaultNode->hasNode('cmf')->willReturn(true);
        $this->defaultNode->getNode('cmf')->willReturn($defaultCmfNode->reveal());

        $defaultBannerNode = $this->prophesize(NodeInterface::class);
        $defaultCmfNode->hasNode('banner')->willReturn(false);
        $defaultCmfNode->addNode('banner')->willReturn($defaultBannerNode->reveal());

        $liveCmfNode = $this->prophesize(NodeInterface::class);
        $this->liveNode->hasNode('cmf')->willReturn(true);
        $this->liveNode->getNode('cmf')->willReturn($liveCmfNode->reveal());

        $liveBannerNode = $this->prophesize(NodeInterface::class);
        $liveCmfNode->hasNode('banner')->willReturn(false);
        $liveCmfNode->addNode('banner')->willReturn($liveBannerNode->reveal());

        $this->persistEvent->setParentNode($defaultBannerNode->reveal())->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }
}
