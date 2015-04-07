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

use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\MetadataFactory;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Behavior\AutoNameBehavior;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\Subscriber\Behavior\AutoNameSubscriber;
use Symfony\Cmf\Bundle\CoreBundle\Slugifier\SlugifierInterface;
use PHPCR\NodeInterface;
use Prophecy\Argument;

class AutoNameSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->slugifier = $this->prophesize(SlugifierInterface::class);
        $this->metadataFactory = $this->prophesize(MetadataFactory::class);
        $this->event = $this->prophesize(PersistEvent::class);
        $this->document = $this->prophesize(AutoNameBehavior::class);
        $this->parentDocument = new \stdClass;
        $this->newNode = $this->prophesize(NodeInterface::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->parent = new \stdClass;

        $this->subscriber = new AutoNameSubscriber(
            $this->documentRegistry->reveal(),
            $this->slugifier->reveal(),
            $this->metadataFactory->reveal()
        );
    }

    /**
     * It should return early if the event already has a node
     */
    public function testAlreadyHasNode()
    {
        $this->event->hasNode()->willReturn(true);
        $this->subscriber->handlePersist($this->event->reveal());
    }

    /**
     * It should return early if the document is not an instance of AutoName behavior
     */
    public function testNotInstanceOfAutoName()
    {
        $document = new \stdClass;
        $this->event->hasNode()->willReturn(false);
        $this->event->getDocument()->willReturn($document);
        $this->subscriber->handlePersist($this->event->reveal());
    }

    /**
     * It should throw an exception if the document has no title
     *
     * @expectedException Sulu\Component\DocumentManager\Exception\DocumentManagerException
     */
    public function testNoTitle()
    {
        $this->event->hasNode()->willReturn(false);
        $this->document->getTitle()->willReturn(null);
        $this->event->getDocument()->willReturn($this->document->reveal());
        $this->subscriber->handlePersist($this->event->reveal());
    }

    /**
     * It should throw an exception if the document has no parent
     *
     * @expectedException Sulu\Component\DocumentManager\Exception\DocumentManagerException
     */
    public function testNoParent()
    {
        $this->event->hasNode()->willReturn(false);
        $this->document->getTitle()->willReturn('hai');
        $this->document->getParent()->willReturn(null);
        $this->event->getDocument()->willReturn($this->document->reveal());
        $this->subscriber->handlePersist($this->event->reveal());
    }

    /**
     * It should assign a name based on the documents title
     */
    public function testAutoName()
    {
        $this->doTestAutoName(array(), 'hai', 'hai');
    }

    /**
     * It should assign an incremented name if a node already exists
     */
    public function testAutoNameAlreadyExists()
    {
        $this->doTestAutoName(array('hai', 'hai-1'), 'hai', 'hai-2');
    }

    private function doTestAutoName($existingNames, $title, $expectedName)
    {
        $phpcrType = 'sulu:test';
        $this->event->hasNode()->willReturn(false);
        $this->document->getTitle()->willReturn($title);
        $this->document->getParent()->willReturn($this->parent);
        $this->event->getDocument()->willReturn($this->document->reveal());
        $this->slugifier->slugify($title)->willReturn($title);

        $this->documentRegistry->getNodeForDocument($this->parent)->willReturn($this->parentNode->reveal());
        $this->metadataFactory->getMetadataForClass(get_class($this->document->reveal()))->willReturn($this->metadata->reveal());

        $this->parentNode->hasNode(Argument::any())->will(function ($args) use ($existingNames) {
            $r = in_array($args[0], $existingNames);
            return $r;
        });

        $this->parentNode->addNode($expectedName)->willReturn($this->newNode->reveal());

        $this->metadata->getPhpcrType()->willReturn($phpcrType);
        $this->newNode->addMixin($phpcrType)->shouldBeCalled();
        $this->newNode->setProperty('jcr:uuid', Argument::type('string'))->shouldBeCalled();
        $this->event->setNode($this->newNode->reveal())->shouldBeCalled();
        $this->subscriber->handlePersist($this->event->reveal());
    }
}
