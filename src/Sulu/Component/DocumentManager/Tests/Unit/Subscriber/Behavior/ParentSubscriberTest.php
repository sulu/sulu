<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior;

use Sulu\Component\DocumentManager\Subscriber\Behavior\TimestampSubscriber;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Behavior\TimestampBehavior;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\DocumentAccessor;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use Sulu\Component\DocumentManager\MetadataFactory;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\Document\UnknownDocument;
use Sulu\Component\DocumentManager\Behavior\ParentBehavior;
use Sulu\Component\DocumentManager\Subscriber\Behavior\ParentSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use ProxyManager\Proxy\LazyLoadingInterface;

class ParentSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->document = new TestParentDocument();
        $this->notImplementing = new \stdClass;
        $this->node = $this->prophesize(NodeInterface::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->metadataFactory = $this->prophesize(MetadataFactory::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->proxyFactory = new LazyLoadingGhostFactory();

        $this->subscriber = new ParentSubscriber(
            $this->proxyFactory,
            $this->dispatcher->reveal(),
            $this->documentRegistry->reveal(),
            $this->metadataFactory->reveal()
        );

        $this->hydrateEvent->getNode()->willReturn($this->node);
    }

    /**
     * It should return early if the document does not implement the ParentBehavior interface
     */
    public function testHydrateNotImplementing()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->notImplementing);
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should populate the documents parent property with a proxy
     */
    public function testHydrateParent()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->document);
        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->metadataFactory->getMetadataForPhpcrNode($this->parentNode->reveal())->willReturn($this->metadata->reveal());
        $this->metadata->getClass()->willReturn(TestParentDocumentParent::class);
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());

        $this->assertInstanceOf(LazyLoadingInterface::class, $this->document->getParent());

        return $this->document;
    }

    /**
     * It should lazy load the proxy
     *
     * @depends testHydrateParent
     */
    public function testHydrateParentLazyProxy($document)
    {
        $this->assertEquals('Hello', $document->getParent()->getTitle());
    }
}

class TestParentDocument implements ParentBehavior
{
    private $parent;

    public function getParent() 
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }
}

class TestParentDocumentParent
{
    public function getTitle()
    {
        return 'Hello';
    }
}
