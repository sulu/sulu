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

use PHPCR\NodeInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Subscriber\ResourceSegmentSubscriber;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\DocumentManagerContext;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;

class ResourceSegmentSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testHydrate()
    {
        $locale = 'de';
        $segment = '/test';
        $propertyName = 'url';
        $localizedPropertyName = sprintf('i18n:%s-%s', $locale, $propertyName);

        $encoder = $this->prophesize(PropertyEncoder::class);
        $inspector = $this->prophesize(DocumentInspector::class);
        $event = $this->prophesize(AbstractMappingEvent::class);
        $context = $this->prophesize(DocumentManagerContext::class);
        $context->getInspector()->willReturn($inspector);
        $event->getContext()->willReturn($context->reveal());

        $document = $this->prophesize(ResourceSegmentBehavior::class);
        $document->willImplement(StructureBehavior::class);
        $event->getDocument()->willReturn($document->reveal());

        $node = $this->prophesize(NodeInterface::class);
        $event->getNode()->willReturn($node->reveal());

        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $inspector->getStructureMetadata($document->reveal())->willReturn($structureMetadata->reveal());

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn($propertyName);
        $structureMetadata->getPropertyByTagName('sulu.rlp')->willReturn($property->reveal());

        $inspector->getOriginalLocale($document->reveal())->willReturn($locale);
        $encoder->localizedSystemName($propertyName, $locale)->willReturn($localizedPropertyName);

        $node->getPropertyValueWithDefault($localizedPropertyName, '')->willReturn($segment);

        // Asserts
        $document->setResourceSegment($segment)->shouldBeCalled();

        $subscriber = new ResourceSegmentSubscriber($encoder->reveal(), $inspector->reveal());
        $subscriber->handleHydrate($event->reveal());
    }

    public function testPersist()
    {
        $locale = 'de';
        $segment = '/test';
        $propertyName = 'url';
        $localizedPropertyName = sprintf('i18n:%s-%s', $locale, $propertyName);

        $encoder = $this->prophesize(PropertyEncoder::class);
        $inspector = $this->prophesize(DocumentInspector::class);
        $event = $this->prophesize(PersistEvent::class);
        $context = $this->prophesize(DocumentManagerContext::class);
        $context->getInspector()->willReturn($inspector);
        $event->getContext()->willReturn($context->reveal());

        $document = $this->prophesize(ResourceSegmentBehavior::class);
        $document->willImplement(StructureBehavior::class);
        $event->getDocument()->willReturn($document->reveal());

        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $inspector->getStructureMetadata($document->reveal())->willReturn($structureMetadata->reveal());

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn($propertyName);
        $structureMetadata->getPropertyByTagName('sulu.rlp')->willReturn($property->reveal());

        $localizedProperty = $this->prophesize(PropertyInterface::class);
        $localizedProperty->getName()->willReturn($localizedPropertyName);

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getProperty($propertyName)->willReturn($localizedProperty->reveal());
        $document->getStructure()->willReturn($structure->reveal());

        $document->getResourceSegment()->willReturn($segment);

        // Asserts
        $localizedProperty->setValue($segment)->shouldBeCalled();

        $subscriber = new ResourceSegmentSubscriber($encoder->reveal(), $inspector->reveal());
        $subscriber->handlePersist($event->reveal());
    }
}
