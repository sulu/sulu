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
use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\Subscriber\ResourceSegmentSubscriber;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;

class ResourceSegmentSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var RlpStrategyInterface
     */
    private $rlpStrategy;

    /**
     * @var ResourceSegmentBehavior
     */
    private $document;

    /**
     * @var StructureMetadata
     */
    private $structureMetadata;

    /**
     * @var ResourceSegmentSubscriber
     */
    private $resourceSegmentSubscriber;

    public function setUp()
    {
        $this->encoder = $this->prophesize(PropertyEncoder::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->rlpStrategy = $this->prophesize(RlpStrategyInterface::class);
        $this->document = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(RedirectTypeBehavior::class);
        $this->structureMetadata = $this->prophesize(StructureMetadata::class);

        $this->documentInspector->getStructureMetadata($this->document->reveal())
            ->willReturn($this->structureMetadata->reveal());

        $this->resourceSegmentSubscriber = new ResourceSegmentSubscriber(
            $this->encoder->reveal(),
            $this->documentInspector->reveal(),
            $this->rlpStrategy->reveal()
        );
    }

    public function testHydrate()
    {
        $locale = 'de';
        $segment = '/test';
        $propertyName = 'url';
        $localizedPropertyName = sprintf('i18n:%s-%s', $locale, $propertyName);

        $event = $this->prophesize(AbstractMappingEvent::class);

        $event->getDocument()->willReturn($this->document->reveal());

        $node = $this->prophesize(NodeInterface::class);
        $event->getNode()->willReturn($node->reveal());

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn($propertyName);
        $this->structureMetadata->getPropertyByTagName('sulu.rlp')->willReturn($property->reveal());

        $this->documentInspector->getOriginalLocale($this->document->reveal())->willReturn($locale);
        $this->encoder->localizedSystemName($propertyName, $locale)->willReturn($localizedPropertyName);

        $node->getPropertyValueWithDefault($localizedPropertyName, '')->willReturn($segment);

        // Asserts
        $this->document->setResourceSegment($segment)->shouldBeCalled();

        $this->resourceSegmentSubscriber->handleHydrate($event->reveal());
    }

    public function testPersistDocument()
    {
        $locale = 'de';
        $segment = '/test';
        $propertyName = 'url';
        $localizedPropertyName = sprintf('i18n:%s-%s', $locale, $propertyName);

        $event = $this->prophesize(PersistEvent::class);

        $event->getDocument()->willReturn($this->document->reveal());

        $property = $this->prophesize(PropertyMetadata::class);
        $property->getName()->willReturn($propertyName);
        $this->structureMetadata->getPropertyByTagName('sulu.rlp')->willReturn($property->reveal());

        $localizedProperty = $this->prophesize(PropertyInterface::class);
        $localizedProperty->getName()->willReturn($localizedPropertyName);

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getProperty($propertyName)->willReturn($localizedProperty->reveal());
        $this->document->getStructure()->willReturn($structure->reveal());

        $this->document->getResourceSegment()->willReturn($segment);

        // Asserts
        $localizedProperty->setValue($segment)->shouldBeCalled();

        $this->resourceSegmentSubscriber->handlePersistDocument($event->reveal());
    }

    public function testPersistDocumentWithoutLocale()
    {
        $event = $this->prophesize(PersistEvent::class);
        $document = $this->prophesize(ResourceSegmentBehavior::class);
        $document->willImplement(StructureBehavior::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getLocale()->willReturn(null)->shouldBeCalled();
        $this->rlpStrategy->save($document->reveal(), Argument::any())->shouldNotBeCalled();

        $this->resourceSegmentSubscriber->handlePersistRoute($event->reveal());
    }

    public function testPersistRoute()
    {
        $event = $this->prophesize(PersistEvent::class);
        $event->getLocale()->willReturn('de');

        $this->document->getRedirectType()->willReturn(RedirectType::NONE);
        $event->getDocument()->willReturn($this->document->reveal());

        $this->rlpStrategy->save($this->document->reveal(), null)->shouldBeCalled();
        $this->resourceSegmentSubscriber->handlePersistRoute($event->reveal());
    }

    public function testPersistRouteForRedirect()
    {
        $event = $this->prophesize(PersistEvent::class);
        $event->getLocale()->willReturn('de');

        $this->document->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $event->getDocument()->willReturn($this->document->reveal());

        $this->rlpStrategy->save(Argument::any())->shouldNotBeCalled();
        $this->resourceSegmentSubscriber->handlePersistRoute($event->reveal());
    }
}
