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
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Behavior\Path\BasePathBehavior;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Path\AliasFilingSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Path\BasePathSubscriber;

class BasePathSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<PersistEvent>
     */
    private $persistEvent;

    /**
     * @var \stdClass
     */
    private $notImplementing;

    /**
     * @var ObjectProphecy<BasePathBehavior>
     */
    private $document;

    /**
     * @var \stdClass
     */
    private $parentDocument;

    /**
     * @var ObjectProphecy<NodeManager>
     */
    private $nodeManager;

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
     * @var AliasFilingSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->notImplementing = new \stdClass();
        $this->document = $this->prophesize(BasePathBehavior::class);
        $this->parentDocument = new \stdClass();
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->documentManager = $this->prophesize(DocumentManager::class);
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);

        $this->subscriber = new BasePathSubscriber(
            $this->nodeManager->reveal(),
            '/base/path'
        );
    }

    /**
     * It should return early if the document is not implementing the behavior.
     */
    public function testPersistNotImplementing(): void
    {
        $this->persistEvent->getDocument()->willReturn($this->notImplementing)->shouldBeCalled();
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should set the parent document.
     */
    public function testSetParentDocument(): void
    {
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->getLocale()->willReturn('fr');
        $this->metadataFactory->getMetadataForClass(\get_class($this->document->reveal()))->willReturn(
            $this->metadata->reveal()
        );
        $this->metadata->getAlias()->willReturn('test');
        $this->nodeManager->createPath('/base/path')->willReturn($this->parentNode->reveal());

        $this->persistEvent->setParentNode($this->parentNode->reveal())->shouldBeCalled();
        $this->documentManager->find('/base/path/tests', 'fr')->willReturn($this->parentDocument);

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }
}
