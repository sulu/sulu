<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\Audit\Path;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Behavior\Path\AliasFilingBehavior;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Path\AliasFilingSubscriber;

class AliasFilingSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<PersistEvent>
     */
    private $persistEvent;

    /**
     * @var ObjectProphecy<AliasFilingBehavior>
     */
    private $document;

    /**
     * @var \stdClass
     */
    private $parentDocument;

    /**
     * @var ObjectProphecy<DocumentManager>
     */
    private $documentManager;

    /**
     * @var ObjectProphecy<MetadataFactoryInterface>
     */
    private $metadataFactory;

    /**
     * @var ObjectProphecy<Metadata>
     */
    private $metadata;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $parentNode;

    /**
     * @var ObjectProphecy<SessionInterface>
     */
    private $defaultSession;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $defaultNode;

    /**
     * @var ObjectProphecy<SessionInterface>
     */
    private $liveSession;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $liveNode;

    /**
     * @var AliasFilingSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->document = $this->prophesize(AliasFilingBehavior::class);
        $this->parentDocument = new \stdClass();
        $this->documentManager = $this->prophesize(DocumentManager::class);
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->defaultSession = $this->prophesize(SessionInterface::class);
        $this->defaultNode = $this->prophesize(NodeInterface::class);
        $this->liveSession = $this->prophesize(SessionInterface::class);
        $this->liveNode = $this->prophesize(NodeInterface::class);

        $this->metadataFactory->getMetadataForClass(\get_class($this->document->reveal()))
            ->willReturn($this->metadata->reveal());
        $this->defaultSession->getRootNode()->willReturn($this->defaultNode->reveal());
        $this->liveSession->getRootNode()->willReturn($this->liveNode->reveal());

        $this->subscriber = new AliasFilingSubscriber(
            $this->defaultSession->reveal(),
            $this->liveSession->reveal(),
            $this->metadataFactory->reveal()
        );
    }

    /**
     * It should return early if the document is not implementing the behavior.
     */
    public function testPersistNotImplementing(): void
    {
        $this->persistEvent->getDocument()->willReturn(new \stdClass())->shouldBeCalled();
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should set the parent document.
     */
    public function testSetParentDocument(): void
    {
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->hasParentNode()->willReturn(true);
        $this->persistEvent->getParentNode()->willReturn($this->parentNode->reveal());
        $this->parentNode->getPath()->willReturn('/cmf');
        $this->metadata->getAlias()->willReturn('banner');

        $defaultCmfNode = $this->prophesize(NodeInterface::class);
        $this->defaultNode->hasNode('cmf')->willReturn(true);
        $this->defaultNode->getNode('cmf')->willReturn($defaultCmfNode->reveal());

        $defaultBannerNode = $this->prophesize(NodeInterface::class);
        $defaultCmfNode->hasNode('banners')->willReturn(false);
        $defaultCmfNode->addNode('banners')->willReturn($defaultBannerNode->reveal());

        $liveCmfNode = $this->prophesize(NodeInterface::class);
        $this->liveNode->hasNode('cmf')->willReturn(true);
        $this->liveNode->getNode('cmf')->willReturn($liveCmfNode->reveal());

        $liveBannerNode = $this->prophesize(NodeInterface::class);
        $liveCmfNode->hasNode('banners')->willReturn(false);
        $liveCmfNode->addNode('banners')->willReturn($liveBannerNode->reveal());

        $this->persistEvent->setParentNode($defaultBannerNode->reveal())->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }
}
