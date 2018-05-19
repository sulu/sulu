<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Core;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\ProxyFactory;
use Sulu\Component\DocumentManager\Subscriber\Core\MappingSubscriber;

class MappingSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var Metadata\
     */
    private $metadata;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var \stdClass
     */
    private $document;

    /**
     * @var DocumentAccessor
     */
    private $accessor;

    /**
     * @var PersistEvent
     */
    private $persistEvent;

    /**
     * @var HydrateEvent
     */
    private $hydrateEvent;

    /**
     * @var ProxyFactory
     */
    private $proxyFactory;

    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    /**
     * @var MappingSubscriber
     */
    private $mappingSubscriber;

    public function setUp()
    {
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->encoder = $this->prophesize(PropertyEncoder::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->document = new \stdClass();
        $this->accessor = $this->prophesize(DocumentAccessor::class);
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->proxyFactory = $this->prophesize(ProxyFactory::class);
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->hydrateEvent->getLocale()->willReturn('de');
        $this->hydrateEvent->getAccessor()->willReturn($this->accessor);
        $this->hydrateEvent->getDocument()->willReturn($this->document);
        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->persistEvent->getLocale()->willReturn('de');
        $this->persistEvent->getAccessor()->willReturn($this->accessor);

        $this->mappingSubscriber = new MappingSubscriber(
            $this->metadataFactory->reveal(),
            $this->encoder->reveal(),
            $this->proxyFactory->reveal(),
            $this->documentRegistry->reveal()
        );

        $this->metadataFactory->getMetadataForClass('stdClass')->willReturn($this->metadata->reveal());
    }

    /**
     * It should map fields to the PHPCR node.
     */
    public function testPersist()
    {
        $this->metadata->getFieldMappings()->willReturn(
            [
                'test' => [
                    'encoding' => 'localized_system',
                    'property' => 'hello',
                    'type' => null,
                    'mapped' => true,
                    'multiple' => false,
                    'default' => null,
                ],
            ]
        );

        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->encoder->encode('localized_system', 'hello', 'de')->willReturn('sys:hello');
        $this->accessor->get('test')->willReturn('goodbye');
        $this->node->setProperty('sys:hello', 'goodbye')->shouldBeCalled();
        $this->mappingSubscriber->handleMapping($this->persistEvent->reveal());
    }

    /**
     * It should not map non-mapped fields to the PHPCR node.
     */
    public function testPersistNonMapped()
    {
        $this->metadata->getFieldMappings()->willReturn(
            [
                'test' => [
                    'encoding' => 'localized_system',
                    'property' => 'hello',
                    'type' => null,
                    'multiple' => false,
                    'default' => null,
                    'mapped' => false,
                ],
            ]
        );

        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->encoder->encode('localized_system', 'hello', 'de')->willReturn('sys:hello');
        $this->node->setProperty('sys:hello', 'goodbye')->shouldNotBeCalled();
        $this->mappingSubscriber->handleMapping($this->persistEvent->reveal());
    }

    /**
     * It should throw an exception when mapped non-array values to non-multiple fields.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testPersistNonArray()
    {
        $this->metadata->getFieldMappings()->willReturn(
            [
                'test' => [
                    'encoding' => 'localized_system',
                    'property' => 'hello',
                    'type' => null,
                    'multiple' => true,
                    'default' => null,
                    'mapped' => true,
                ],
            ]
        );

        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->encoder->encode('localized_system', 'hello', 'de')->willReturn('sys:hello');
        $this->accessor->get('test')->willReturn('goodbye');
        $this->mappingSubscriber->handleMapping($this->persistEvent->reveal());
    }

    /**
     * It should map fields from the PHPCR node.
     */
    public function testHydrate()
    {
        $this->metadata->getFieldMappings()->willReturn(
            [
                'test' => [
                    'encoding' => 'localized_system',
                    'property' => 'hello',
                    'mapped' => true,
                    'type' => null,
                    'multiple' => false,
                    'default' => null,
                ],
            ]
        );

        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->encoder->encode('localized_system', 'hello', 'de')->willReturn('sys:hello');
        $this->metadataFactory->hasMetadataForClass('stdClass')->willReturn(true);
        $this->node->getPropertyValueWithDefault('sys:hello', null)->willReturn('goodbye');
        $this->accessor->set('test', 'goodbye')->shouldBeCalled();

        $this->mappingSubscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should not map non-mapped fields.
     */
    public function testHydrateNonMapped()
    {
        $this->metadata->getFieldMappings()->willReturn(
            [
                'test' => [
                    'encoding' => 'localized_system',
                    'property' => 'hello',
                    'mapped' => false,
                    'type' => null,
                    'multiple' => false,
                    'default' => null,
                ],
            ]
        );

        $this->metadataFactory->hasMetadataForClass('stdClass')->willReturn(true);
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->accessor->set('test', 'goodbye')->shouldNotBeCalled();

        $this->mappingSubscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should use a default value.
     */
    public function testHydrateDefault()
    {
        $this->metadata->getFieldMappings()->willReturn(
            [
                'test' => [
                    'encoding' => 'localized_system',
                    'property' => 'hello',
                    'mapped' => false,
                    'type' => null,
                    'multiple' => false,
                    'default' => 'HAI',
                ],
            ]
        );

        $this->metadataFactory->hasMetadataForClass('stdClass')->willReturn(true);
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->encoder->encode('localized_system', 'hello', 'de')->willReturn('sys:hello');
        $this->node->getPropertyValueWithDefault('sys:hello', null)->willReturn(null);
        $this->accessor->set('test', 'HAI')->shouldNotBeCalled();

        $this->mappingSubscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should json_encode the data.
     */
    public function testPersistJsonArray()
    {
        $this->metadata->getFieldMappings()->willReturn(
            [
                'test' => [
                    'encoding' => 'system',
                    'property' => 'hello',
                    'type' => 'json_array',
                    'mapped' => true,
                    'multiple' => false,
                    'default' => null,
                ],
            ]
        );

        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->encoder->encode('system', 'hello', 'de')->willReturn('sys:hello');
        $this->accessor->get('test')->willReturn(['key' => 'value']);
        $this->node->setProperty('sys:hello', json_encode(['key' => 'value']))->shouldBeCalled();
        $this->mappingSubscriber->handleMapping($this->persistEvent->reveal());
    }

    /**
     * It should json_decode the data.
     */
    public function testHydrateJsonArray()
    {
        $this->metadata->getFieldMappings()->willReturn(
            [
                'test' => [
                    'encoding' => 'system',
                    'property' => 'hello',
                    'mapped' => true,
                    'type' => 'json_array',
                    'multiple' => false,
                    'default' => null,
                ],
            ]
        );

        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->encoder->encode('system', 'hello', 'de')->willReturn('sys:hello');
        $this->metadataFactory->hasMetadataForClass('stdClass')->willReturn(true);
        $this->node->getPropertyValueWithDefault('sys:hello', null)->willReturn(json_encode(['key' => 'value']));
        $this->accessor->set('test', ['key' => 'value'])->shouldBeCalled();

        $this->mappingSubscriber->handleHydrate($this->hydrateEvent->reveal());
    }
}
