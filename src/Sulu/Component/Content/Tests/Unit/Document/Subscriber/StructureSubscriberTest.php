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

use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Structure\ManagedStructure;
use Sulu\Component\Content\Document\Structure\PropertyValue;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\Content\Document\Subscriber\PHPCR\SuluNode;
use Sulu\Component\Content\Document\Subscriber\StructureSubscriber;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class StructureSubscriberTest extends SubscriberTestCase
{
    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var PropertyMetadata
     */
    private $structureProperty;

    /**
     * @var ContentTypeInterface
     */
    private $contentType;

    /**
     * @var PropertyValue
     */
    private $propertyValue;

    /**
     * @var TranslatedProperty
     */
    private $legacyProperty;

    /**
     * @var StructureMetadata
     */
    private $structureMetadata;

    /**
     * @var ManagedStructure
     */
    private $structure;

    /**
     * @var LegacyPropertyFactory
     */
    private $propertyFactory;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var StructureBehavior
     */
    private $document;

    /**
     * @var StructureSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        parent::setUp();
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);

        $this->structureProperty = $this->prophesize(PropertyMetadata::class);
        $this->contentType = $this->prophesize(ContentTypeInterface::class);
        $this->propertyValue = $this->prophesize(PropertyValue::class);
        $this->legacyProperty = $this->prophesize(TranslatedProperty::class);
        $this->structureMetadata = $this->prophesize(StructureMetadata::class);
        $this->structure = $this->prophesize(ManagedStructure::class);
        $this->propertyFactory = $this->prophesize(LegacyPropertyFactory::class);
        $this->document = $this->prophesize(StructureBehavior::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);

        $this->document->getStructureType()->willReturn('foobar');
        $this->inspector->getStructureMetadata(Argument::any())->willReturn($this->structureMetadata);
        $this->persistEvent->getLocale()->willReturn('en');

        $this->structureMetadata->getName()->willReturn('foobar');

        $this->subscriber = new StructureSubscriber(
            $this->encoder->reveal(),
            $this->contentTypeManager->reveal(),
            $this->inspector->reveal(),
            $this->propertyFactory->reveal(),
            $this->webspaceManager->reveal(),
            ['article' => 'foobar']
        );
    }

    public function testPersistStructureType()
    {
        $this->document->getStructure()->willReturn($this->structure->reveal());
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->structure->setStructureMetadata($this->structureMetadata->reveal())->shouldBeCalled();

        $this->subscriber->handlePersistStructureType($this->persistEvent->reveal());
    }

    public function testPersistStagedProperties()
    {
        $this->document->getStructure()->willReturn($this->structure->reveal());
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->getOption('clear_missing_content')->willReturn(true);
        $this->structure->commitStagedData(Argument::any())->shouldBeCalled();
        $this->subscriber->handlePersistStagedProperties($this->persistEvent->reveal());
    }

    /**
     * It should return early if the document is not implementing the behavior.
     */
    public function testPersistNotImplementing()
    {
        $this->persistEvent->getDocument()->willReturn($this->notImplementing);
        $this->subscriber->saveStructureData($this->persistEvent->reveal());
    }

    /**
     * It should return early if the structure type is empty.
     */
    public function testPersistNoStructureType()
    {
        $this->document->getStructureType()->willReturn(null);
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->subscriber->saveStructureData($this->persistEvent->reveal());
    }

    /**
     * It should return early if the locale is null.
     */
    public function testPersistNoLocale()
    {
        $this->persistEvent->getLocale()->willReturn(null);
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());

        $this->subscriber->saveStructureData($this->persistEvent->reveal());

        $this->node->setProperty()->shouldNotBeCalled();
    }

    /**
     * It should set the structure type and map the content to thethe node.
     */
    public function testPersist()
    {
        $this->document->getStructure()->willReturn($this->structure->reveal());
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->getOptions()->willReturn([
            'ignore_required' => false,
        ]);

        // map the structure type
        $this->persistEvent->getLocale()->willReturn('fr');
        $this->encoder->contentName('template')->willReturn('i18n:fr-template');
        $this->node->setProperty('i18n:fr-template', 'foobar')->shouldBeCalled();

        // map the content
        $this->inspector->getStructureMetadata($this->document->reveal())->willReturn($this->structureMetadata->reveal());
        $this->inspector->getWebspace($this->document->reveal())->willReturn('webspace');
        $this->structureMetadata->getProperties()->willReturn([
            'prop1' => $this->structureProperty->reveal(),
        ]);
        $this->setupPropertyWrite();

        $this->subscriber->saveStructureData($this->persistEvent->reveal());
    }

    /**
     * It should throw an exception if the property is required but the value is null.
     *
     * @expectedException Sulu\Component\Content\Exception\MandatoryPropertyException
     */
    public function testThrowExceptionPropertyRequired()
    {
        $this->document->getStructure()->willReturn($this->structure->reveal());
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->getOptions()->willReturn([
            'ignore_required' => false,
        ]);

        // map the structure type
        $this->persistEvent->getLocale()->willReturn('fr');

        // map the content
        $this->inspector->getStructureMetadata($this->document->reveal())->willReturn($this->structureMetadata->reveal());
        $this->inspector->getWebspace($this->document->reveal())->willReturn('webspace');
        $this->structureMetadata->getProperties()->willReturn([
            'prop1' => $this->structureProperty->reveal(),
        ]);
        $this->structureProperty->isRequired()->willReturn(true);
        $this->structure->getProperty('prop1')->willReturn($this->propertyValue->reveal());
        $this->propertyValue->getValue()->willReturn(null);
        $this->structureMetadata->getName()->willReturn('test');
        $this->structureMetadata->getResource()->willReturn('/path/to/resource.xml');

        $this->subscriber->saveStructureData($this->persistEvent->reveal());
    }

    /**
     * It should ignore required properties if the `ignore_required` option is given.
     */
    public function testIgnoreRequiredProperties()
    {
        $this->document->getStructure()->willReturn($this->structure->reveal());
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->getOptions()->willReturn([
            'ignore_required' => true,
        ]);

        // map the structure type
        $this->persistEvent->getLocale()->willReturn('fr');

        // map the content
        $this->inspector->getStructureMetadata($this->document->reveal())->willReturn($this->structureMetadata->reveal());
        $this->inspector->getWebspace($this->document->reveal())->willReturn('webspace');
        $this->structureMetadata->getProperties()->willReturn([
            'prop1' => $this->structureProperty->reveal(),
        ]);
        $this->structureProperty->isRequired()->willReturn(true);
        $this->structure->getProperty('prop1')->willReturn($this->propertyValue->reveal());
        $this->propertyValue->getValue()->willReturn(null);
        $this->structureMetadata->getName()->willReturn('test');
        $this->structureMetadata->getResource()->willReturn('/path/to/resource.xml');

        $this->setupPropertyWrite();

        $this->subscriber->saveStructureData($this->persistEvent->reveal());
    }

    /**
     * It should return early when not implementing.
     */
    public function testHydrateNotImplementing()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->notImplementing);

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should create a ManagedStructure when the structure property is set in the node.
     */
    public function testHydrate()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->document->reveal());
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getLocale()->willReturn('fr');
        $this->hydrateEvent->getOption('load_ghost_content', false)->willReturn(true);
        $this->hydrateEvent->getOption('rehydrate')->willReturn(false);

        // set the structure type
        $this->encoder->contentName('template')->willReturn('i18n:fr-template');
        $this->node->getPropertyValueWithDefault('i18n:fr-template', null)->willReturn('foobar');

        $this->document->setStructureType('foobar')->shouldBeCalled();

        // set the property container
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
        $this->accessor->set('structure', Argument::type(ManagedStructure::class))->shouldHaveBeenCalled();
    }

    /**
     * It should create a new default Structure when there is no structure property.
     */
    public function testHydrateNewStructure()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->document->reveal());
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getLocale()->willReturn('fr');
        $this->hydrateEvent->getOption('load_ghost_content', false)->willReturn(true);
        $this->hydrateEvent->getOption('rehydrate')->willReturn(false);

        // set the structure type
        $this->encoder->contentName('template')->willReturn('i18n:fr-template');
        $this->node->getPropertyValueWithDefault('i18n:fr-template', null)->willReturn(null);

        $this->document->setStructureType(null)->shouldBeCalled();
        $this->document->getStructure()->willReturn(null);

        // set the property container
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
        $this->accessor->set('structure', Argument::type(Structure::class))->shouldHaveBeenCalled();
    }

    /**
     * It should create a new default Structure when there is no structure property.
     */
    public function testHydrateNewStructureDefaultArray()
    {
        $metadata = $this->prophesize(Metadata::class);
        $metadata->getAlias()->willReturn('article');

        $this->inspector->getMetadata($this->document->reveal())->willReturn($metadata->reveal());
        $this->inspector->getWebspace($this->document->reveal())->willReturn(null);

        $this->hydrateEvent->getDocument()->willReturn($this->document->reveal());
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getLocale()->willReturn('fr');
        $this->hydrateEvent->getOption('load_ghost_content', false)->willReturn(true);
        $this->hydrateEvent->getOption('rehydrate')->willReturn(true);

        // set the structure type
        $this->encoder->contentName('template')->willReturn('i18n:fr-template');
        $this->node->getPropertyValueWithDefault('i18n:fr-template', null)->willReturn(null);

        $this->document->setStructureType('foobar')->shouldBeCalled();
        $this->document->getStructure()->willReturn(null);

        // set the property container
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
        $this->accessor->set('structure', Argument::type(Structure::class))->shouldHaveBeenCalled();
    }

    /**
     * If the document already has a structure and there is no structure on the node (i.e.
     * it is a new document) then use the Structure which is already set.
     */
    public function testHydrateNewStructureNoRehydrate()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->document->reveal());
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getLocale()->willReturn('fr');
        $this->hydrateEvent->getOption('load_ghost_content', false)->willReturn(true);
        $this->hydrateEvent->getOption('rehydrate')->willReturn(false);

        // set the structure type
        $this->encoder->contentName('template')->willReturn('i18n:fr-template');
        $this->node->getPropertyValueWithDefault('i18n:fr-template', null)->willReturn(null);

        $this->document->setStructureType(null)->shouldBeCalled();
        $this->document->getStructure()->willReturn($this->structure->reveal());

        // set the property container
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
        $this->accessor->set('structure', Argument::type(ManagedStructure::class))->shouldHaveBeenCalled();
    }

    /**
     * If the document already has a structure and there is no structure on the node (i.e.
     * it is a new document) then use the Structure which is already set.
     */
    public function testHydrateNewStructureRehydrate()
    {
        $metadata = $this->prophesize(Metadata::class);
        $metadata->getAlias()->willReturn('page');

        $this->hydrateEvent->getDocument()->willReturn($this->document->reveal());
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getLocale()->willReturn('fr');
        $this->hydrateEvent->getOption('load_ghost_content', false)->willReturn(true);
        $this->hydrateEvent->getOption('rehydrate')->willReturn(true);
        $this->inspector->getWebspace($this->document->reveal())->willReturn('sulu_io');
        $this->inspector->getMetadata($this->document->reveal())->willReturn($metadata->reveal());

        $webspace = new Webspace();
        $webspace->addDefaultTemplate('page', 'default');

        $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn($webspace);

        // set the structure type
        $this->encoder->contentName('template')->willReturn('i18n:fr-template');
        $this->node->getPropertyValueWithDefault('i18n:fr-template', null)->willReturn(null);

        $this->document->setStructureType('default')->shouldBeCalled();
        $this->document->getStructure()->willReturn($this->structure->reveal());

        // set the property container
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
        $this->accessor->set('structure', Argument::type(ManagedStructure::class))->shouldHaveBeenCalled();
    }

    private function setupPropertyWrite()
    {
        $this->structureProperty->isRequired()->willReturn(true);
        $this->structureProperty->getContentTypeName()->willReturn('content_type');
        $this->contentTypeManager->get('content_type')->willReturn($this->contentType->reveal());
        $this->propertyFactory->createTranslatedProperty($this->structureProperty->reveal(), 'fr')->willReturn($this->legacyProperty->reveal());
        $this->structure->getProperty('prop1')->willReturn($this->propertyValue->reveal());
        $this->propertyValue->getValue()->willReturn('test');

        $this->contentType->remove(
            $this->node->reveal(),
            $this->legacyProperty->reveal(),
            'webspace',
            'fr',
            null
        )->shouldNotBeCalled();

        $this->contentType->write(
            new SuluNode($this->node->reveal()),
            $this->legacyProperty->reveal(),
            null,
            'webspace',
            'fr',
            null
        )->shouldBeCalled();
    }
}
