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

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\Content\Document\Property\PropertyContainer;
use Sulu\Component\Content\Document\Subscriber\ContentSubscriber;
use Sulu\Component\Content\Structure\Factory\StructureFactory;
use Sulu\Component\Content\Structure\Property;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactory;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Document\Property\PropertyValue;

class ContentSubscriberTest extends SubscriberTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);

        $this->structureProperty = $this->prophesize(Property::class);
        $this->contentType = $this->prophesize(ContentTypeInterface::class);
        $this->propertyValue = $this->prophesize(PropertyValue::class);
        $this->legacyProperty = $this->prophesize(TranslatedProperty::class);
        $this->structure = $this->prophesize(Structure::class);
        $this->propertyContainer = $this->prophesize(PropertyContainer::class);
        $this->propertyFactory = $this->prophesize(LegacyPropertyFactory::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);

        $this->subscriber = new ContentSubscriber(
            $this->encoder->reveal(),
            $this->contentTypeManager->reveal(),
            $this->inspector->reveal(),
            $this->propertyFactory->reveal()
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
        $this->persistEvent->getDocument()->willReturn($document);
        $this->subscriber->handlePersist($this->persistEvent->reveal());
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
        $this->inspector->getStructure($document)->willReturn($this->structure->reveal());
        $this->inspector->getWebspace($document)->willReturn('webspace');
        $this->structure->getChildren()->willReturn(array(
            'prop1' => $this->structureProperty->reveal()
        ));
        $this->structureProperty->getContentTypeName()->willReturn('content_type');
        $this->contentTypeManager->get('content_type')->willReturn($this->contentType->reveal());
        $this->propertyFactory->createTranslatedProperty($this->structureProperty->reveal(), 'fr')->willReturn($this->legacyProperty->reveal());
        $this->propertyContainer->getProperty('prop1')->willReturn($this->propertyValue->reveal());

        $this->contentType->write(
            $this->node->reveal(),
            $this->legacyProperty->reveal(),
            null,
            'webspace',
            'fr',
            null
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
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
        $this->assertEquals('foobar', $document->getStructureType());
        $this->accessor->set('content', Argument::type(PropertyContainer::class))->shouldHaveBeenCalled();
    }

}

class TestContentDocument implements ContentBehavior
{
    private $structureType;
    private $content;
    private $locale;

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

    public function getLocale() 
    {
        return $this->locale;
    }
    
}
