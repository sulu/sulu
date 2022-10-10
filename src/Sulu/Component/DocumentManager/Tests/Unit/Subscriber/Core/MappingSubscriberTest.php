<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Core;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\ProxyFactory;
use Sulu\Component\DocumentManager\Subscriber\Core\MappingSubscriber;

class MappingSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MetadataFactoryInterface>
     */
    private $metadataFactory;

    /**
     * @var ObjectProphecy<PropertyEncoder>
     */
    private $encoder;

    /**
     * @var ObjectProphecy<Metadata>
     */
    private $metadata;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var \stdClass
     */
    private $document;

    /**
     * @var ObjectProphecy<DocumentAccessor>
     */
    private $accessor;

    /**
     * @var ObjectProphecy<PersistEvent>
     */
    private $persistEvent;

    /**
     * @var ObjectProphecy<HydrateEvent>
     */
    private $hydrateEvent;

    /**
     * @var ObjectProphecy<ProxyFactory>
     */
    private $proxyFactory;

    /**
     * @var ObjectProphecy<DocumentRegistry>
     */
    private $documentRegistry;

    /**
     * @var MappingSubscriber
     */
    private $mappingSubscriber;

    public function setUp(): void
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
    public function testPersist(): void
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
    public function testPersistNonMapped(): void
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
     */
    public function testPersistNonArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
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
    public function testHydrate(): void
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
    public function testHydrateNonMapped(): void
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
    public function testHydrateDefault(): void
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
    public function testPersistJsonArray(): void
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
        $this->node->setProperty('sys:hello', \json_encode(['key' => 'value']))->shouldBeCalled();
        $this->mappingSubscriber->handleMapping($this->persistEvent->reveal());
    }

    /**
     * It should json_decode the data.
     */
    public function testHydrateJsonArray(): void
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
        $this->node->getPropertyValueWithDefault('sys:hello', null)->willReturn(\json_encode(['key' => 'value']));
        $this->accessor->set('test', ['key' => 'value'])->shouldBeCalled();

        $this->mappingSubscriber->handleHydrate($this->hydrateEvent->reveal());
    }
}
