<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use PHPCR\NodeInterface;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\Content\Document\Subscriber\ContentSubscriber;

class ContentSubscriberTest extends SubscriberTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->subscriber = new ContentSubscriber($this->encoder->reveal());
    }

    /**
     * It should return early if the document is not implementing the behavior
     */
    public function testPersistNotImplementing()
    {
        $this->persistEvent->getDocument()->willReturn($this->notImplementing);
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should set the structure type on the node
     */
    public function testPersist()
    {
        $document = new TestContentDocument();
        $document->setStructureType('foobar');
        $this->persistEvent->getDocument()->willReturn($document);
        $this->persistEvent->getLocale()->willReturn('fr');
        $this->encoder->localizedSystemName('template', 'fr')->willReturn('i18n:fr-template');
        $this->node->setProperty('i18n:fr-template', 'foobar')->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should return early when not implementing
     */
    public function testHydrateNotImplementing()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->notImplementing);
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should set the created and updated fields on the document
     */
    public function testHydrate()
    {
        $document = new TestContentDocument();
        $this->hydrateEvent->getDocument()->willReturn($document);
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getLocale()->willReturn('fr');
        $this->encoder->localizedSystemName('template', 'fr')->willReturn('i18n:fr-template');
        $this->node->getPropertyValueWithDefault('i18n:fr-template', null)->willReturn('hello');

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
        $this->assertEquals('hello', $document->getStructureType());
    }

}

class TestContentDocument implements ContentBehavior
{
    private $structureType;

    public function getStructureType() 
    {
        return $this->structureType;
    }
    
    public function setStructureType($structureType)
    {
        $this->structureType = $structureType;
    }
    
}
