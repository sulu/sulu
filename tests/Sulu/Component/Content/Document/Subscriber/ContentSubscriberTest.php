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
use Sulu\Component\Content\Document\Property\PropertyInterface;
use Sulu\Component\DocumentManager\MetadataFactory;
use Sulu\Component\Content\Type\ContentTypeManagerInterface;
use Sulu\Component\Content\Structure\Factory\StructureFactory;
use Sulu\Component\Content\Structure\Property;
use Sulu\Component\Content\Type\ContentTypeInterface;
use Sulu\Component\Content\Structure\Structure;
use Sulu\Component\Content\Document\Property\PropertyContainer;
use Sulu\Component\DocumentManager\Metadata;

class ContentSubscriberTest extends SubscriberTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->metadataFactory = $this->prophesize(MetadataFactory::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->structureFactory = $this->prophesize(StructureFactory::class);

        $this->structureProperty = $this->prophesize(Property::class);
        $this->contentType = $this->prophesize(ContentTypeInterface::class);
        $this->contentProperty = $this->prophesize(PropertyInterface::class);
        $this->structure = $this->prophesize(Structure::class);
        $this->propertyContainer = $this->prophesize(PropertyContainer::class);

        $this->subscriber = new ContentSubscriber(
            $this->encoder->reveal(),
            $this->contentTypeManager->reveal(),
            $this->metadataFactory->reveal(),
            $this->structureFactory->reveal()
        );
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
     * It should return early if the structure type is empty
     */

    public function testPersistNoStructureType()
    {
        $document = new TestContentDocument($this->propertyContainer->reveal());

        // map the structure type
        $this->persistEvent->getLocale()->willReturn('fr');
        $this->encoder->localizedSystemName('template', 'fr')->willReturn('i18n:fr-template');
        $this->node->setProperty('i18n:fr-template', 'foobar')->shouldBeCalled();
    }

    /**
     * It should set the structure type and map the content to thethe node
     */
    public function testPersist()
    {
        $document = new TestContentDocument($this->propertyContainer->reveal());
        $document->setStructureType('foobar');
        $this->persistEvent->getDocument()->willReturn($document);

        // map the structure type
        $this->persistEvent->getLocale()->willReturn('fr');
        $this->encoder->localizedSystemName('template', 'fr')->willReturn('i18n:fr-template');
        $this->node->setProperty('i18n:fr-template', 'foobar')->shouldBeCalled();

        // map the content
        $this->metadataFactory->getMetadataForClass(Argument::type('string'))->willReturn($this->metadata->reveal());
        $this->metadata->getAlias()->willReturn('hello');
        $this->structureFactory->getStructure('hello', 'foobar')->willReturn($this->structure->reveal());
        $this->structure->getChildren()->willReturn(array(
            'prop1' => $this->structureProperty->reveal()
        ));
        $this->structureProperty->getContentTypeName()->willReturn('content_type');
        $this->contentTypeManager->get('content_type')->willReturn($this->contentType->reveal());
        $this->propertyContainer->getProperty('prop1')->willReturn($this->contentProperty->reveal());
        $this->contentType->write(
            $this->node->reveal(),
            $this->contentProperty->reveal(),
            null, null, null, null
        )->shouldBeCalled();

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

        // set the structure type
        $this->encoder->localizedSystemName('template', 'fr')->willReturn('i18n:fr-template');
        $this->node->getPropertyValueWithDefault('i18n:fr-template', null)->willReturn('foobar');

        // set the property container
        $this->metadataFactory->getMetadataForClass(Argument::type('string'))->willReturn($this->metadata->reveal());
        $this->metadata->getAlias()->willReturn('hello');
        $this->structureFactory->getStructure('hello', 'foobar')->willReturn($this->structure->reveal());

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
        $this->assertEquals('foobar', $document->getStructureType());
        $this->accessor->set('content', Argument::type(PropertyContainer::class))->shouldHaveBeenCalled();
    }

}

class TestContentDocument implements ContentBehavior
{
    private $structureType;
    private $content;

    public function __construct(PropertyContainer $content = null)
    {
        $this->content = $content;
    }

    public function getStructureType() 
    {
        return $this->structureType;
    }
    
    public function setStructureType($structureType)
    {
        $this->structureType = $structureType;
    }

    public function getContent() 
    {
        return $this->content;
    }
}
