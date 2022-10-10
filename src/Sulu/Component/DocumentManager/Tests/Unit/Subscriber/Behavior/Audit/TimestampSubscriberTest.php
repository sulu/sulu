<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\Audit;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\DocumentManager\Behavior\Audit\LocalizedTimestampBehavior;
use Sulu\Component\DocumentManager\Behavior\Audit\TimestampBehavior;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Audit\TimestampSubscriber;

class TimestampSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<PropertyEncoder>
     */
    private $propertyEncoder;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $documentInspector;

    /**
     * @var TimestampSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->subscriber = new TimestampSubscriber(
            $this->propertyEncoder->reveal(),
            $this->documentInspector->reveal()
        );
    }

    public function testSetTimestampsOnNodeForPersistNotImplementing(): void
    {
        $event = $this->prophesize(PersistEvent::class);
        $event->getDocument()->willReturn(new \stdClass())->shouldBeCalled();
        $this->subscriber->setTimestampsOnNodeForPersist($event->reveal());
    }

    public function testSetTimestampsOnNodeForPersistCreatedWhenNull(): void
    {
        $event = $this->prophesize(PersistEvent::class);
        $accessor = $this->prophesize(DocumentAccessor::class);
        $document = $this->prophesize(LocalizedTimestampBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getNode()->willReturn($node->reveal());
        $this->documentInspector->getOriginalLocale($document->reveal())->willReturn('de');

        $this->propertyEncoder->encode('system_localized', 'created', 'de')->willReturn('i18n:de-created');
        $this->propertyEncoder->encode('system_localized', 'changed', 'de')->willReturn('i18n:de-changed');

        $node->hasProperty('i18n:de-created')->willReturn(false);
        $accessor->set('created', Argument::type(\DateTime::class))->shouldBeCalled();
        $node->setProperty('i18n:de-created', Argument::type(\DateTime::class))->shouldBeCalled();

        $accessor->set('changed', Argument::type(\DateTime::class))->shouldBeCalled();
        $node->setProperty('i18n:de-changed', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->subscriber->setTimestampsOnNodeForPersist($event->reveal());
    }

    public function testSetTimestampsOnNodeForPersistCreatedWhenSet(): void
    {
        $event = $this->prophesize(PersistEvent::class);
        $accessor = $this->prophesize(DocumentAccessor::class);
        $document = $this->prophesize(LocalizedTimestampBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getNode()->willReturn($node->reveal());
        $this->documentInspector->getOriginalLocale($document->reveal())->willReturn('de');

        $this->propertyEncoder->encode('system_localized', 'created', 'de')->willReturn('i18n:de-created');
        $this->propertyEncoder->encode('system_localized', 'changed', 'de')->willReturn('i18n:de-changed');

        $createdDate = new \DateTime('2013-01-12');
        $document->getCreated()->willReturn($createdDate);
        $node->hasProperty('i18n:de-created')->willReturn();
        $accessor->set('created', $createdDate)->shouldBeCalled();
        $node->setProperty('i18n:de-created', $createdDate)->shouldBeCalled();

        $accessor->set('changed', Argument::type(\DateTime::class))->shouldBeCalled();
        $node->setProperty('i18n:de-changed', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->subscriber->setTimestampsOnNodeForPersist($event->reveal());
    }

    public function testSetTimestampsOnNodeForPersistChanged(): void
    {
        $event = $this->prophesize(PersistEvent::class);
        $accessor = $this->prophesize(DocumentAccessor::class);
        $document = $this->prophesize(LocalizedTimestampBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getNode()->willReturn($node->reveal());
        $this->documentInspector->getOriginalLocale($document->reveal())->willReturn('de');

        $this->propertyEncoder->encode('system_localized', 'created', 'de')->willReturn('i18n:de-created');
        $node->hasProperty('i18n:de-created')->willReturn(true);
        $accessor->set('created', Argument::type(\DateTime::class))->shouldNotBeCalled();
        $node->setProperty('i18n:de-created', Argument::type(\DateTime::class))->shouldNotBeCalled();

        $this->propertyEncoder->encode('system_localized', 'changed', 'de')->willReturn('i18n:de-changed');
        $accessor->set('changed', Argument::type('DateTime'))->shouldBeCalled();
        $node->setProperty('i18n:de-changed', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->subscriber->setTimestampsOnNodeForPersist($event->reveal());
    }

    public function testSetTimestampsOnNodeForPersistNonLocalized(): void
    {
        $event = $this->prophesize(PersistEvent::class);
        $accessor = $this->prophesize(DocumentAccessor::class);
        $document = $this->prophesize(TimestampBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getNode()->willReturn($node->reveal());
        $this->documentInspector->getOriginalLocale($document->reveal())->willReturn('de');

        $this->propertyEncoder->encode('system', 'created', 'de')->willReturn('created');
        $this->propertyEncoder->encode('system', 'changed', 'de')->willReturn('changed');

        $node->hasProperty('created')->willReturn(false);
        $accessor->set('created', Argument::type(\DateTime::class))->shouldBeCalled();
        $node->setProperty('created', Argument::type(\DateTime::class))->shouldBeCalled();

        $accessor->set('changed', Argument::type(\DateTime::class))->shouldBeCalled();
        $node->setProperty('changed', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->subscriber->setTimestampsOnNodeForPersist($event->reveal());
    }

    public function testSetTimestampOnNodeForPersistLocalizedWithoutLocale(): void
    {
        $event = $this->prophesize(PersistEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(LocalizedTimestampBehavior::class);
        $accessor = $this->prophesize(DocumentAccessor::class);

        $event->getNode()->willReturn($node->reveal());
        $event->getDocument()->willReturn($document->reveal());
        $event->getAccessor()->willReturn($accessor->reveal());
        $this->documentInspector->getOriginalLocale($document->reveal())->willReturn(null);

        $this->propertyEncoder->encode(Argument::cetera())->shouldNotBeCalled();
        $node->setProperty(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->setTimestampsOnNodeForPersist($event->reveal());
    }

    public function testSetTimestampsOnNodeForPublishNotImplementing(): void
    {
        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn(new \stdClass())->shouldBeCalled();
        $this->subscriber->setTimestampsOnNodeForPublish($event->reveal());
    }

    public function testSetTimestampsOnNodeForPublishCreatedWhenNull(): void
    {
        $event = $this->prophesize(PublishEvent::class);
        $accessor = $this->prophesize(DocumentAccessor::class);
        $document = $this->prophesize(LocalizedTimestampBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getNode()->willReturn($node->reveal());
        $this->documentInspector->getOriginalLocale($document->reveal())->willReturn('de');

        $createdDate = new \DateTime('2017-01-25');
        $changedDate = new \DateTime('2017-01-18');

        $document->getCreated()->willReturn($createdDate);
        $document->getChanged()->willReturn($changedDate);

        $this->propertyEncoder->encode('system_localized', 'created', 'de')->willReturn('i18n:de-created');
        $this->propertyEncoder->encode('system_localized', 'changed', 'de')->willReturn('i18n:de-changed');

        $node->hasProperty('i18n:de-created')->willReturn(false);
        $accessor->set('created', $createdDate)->shouldBeCalled();
        $node->setProperty('i18n:de-created', $createdDate)->shouldBeCalled();

        $accessor->set('changed', $changedDate)->shouldBeCalled();
        $node->setProperty('i18n:de-changed', $changedDate)->shouldBeCalled();

        $this->subscriber->setTimestampsOnNodeForPublish($event->reveal());
    }

    public function testSetTimestampsOnNodeForPublishChanged(): void
    {
        $event = $this->prophesize(PublishEvent::class);
        $accessor = $this->prophesize(DocumentAccessor::class);
        $document = $this->prophesize(LocalizedTimestampBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getNode()->willReturn($node->reveal());
        $this->documentInspector->getOriginalLocale($document->reveal())->willReturn('de');

        $createdDate = new \DateTime('2017-01-25');
        $changedDate = new \DateTime('2017-01-18');

        $document->getCreated()->willReturn($createdDate);
        $document->getChanged()->willReturn($changedDate);

        $this->propertyEncoder->encode('system_localized', 'created', 'de')->willReturn('i18n:de-created');
        $node->hasProperty('i18n:de-created')->willReturn(true);
        $accessor->set('created', $createdDate)->shouldNotBeCalled();
        $node->setProperty('i18n:de-created', $createdDate)->shouldNotBeCalled();

        $this->propertyEncoder->encode('system_localized', 'changed', 'de')->willReturn('i18n:de-changed');
        $accessor->set('changed', $changedDate)->shouldBeCalled();
        $node->setProperty('i18n:de-changed', $changedDate)->shouldBeCalled();

        $this->subscriber->setTimestampsOnNodeForPublish($event->reveal());
    }

    public function testSetTimestampsOnNodeForPublishNonLocalized(): void
    {
        $event = $this->prophesize(PublishEvent::class);
        $accessor = $this->prophesize(DocumentAccessor::class);
        $document = $this->prophesize(TimestampBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getNode()->willReturn($node->reveal());
        $this->documentInspector->getOriginalLocale($document->reveal())->willReturn('de');

        $createdDate = new \DateTime('2017-01-25');
        $changedDate = new \DateTime('2017-01-18');

        $document->getCreated()->willReturn($createdDate);
        $document->getChanged()->willReturn($changedDate);

        $this->propertyEncoder->encode('system', 'created', 'de')->willReturn('created');
        $this->propertyEncoder->encode('system', 'changed', 'de')->willReturn('changed');

        $node->hasProperty('created')->willReturn(false);
        $accessor->set('created', $createdDate)->shouldBeCalled();
        $node->setProperty('created', $createdDate)->shouldBeCalled();

        $accessor->set('changed', $changedDate)->shouldBeCalled();
        $node->setProperty('changed', $changedDate)->shouldBeCalled();

        $this->subscriber->setTimestampsOnNodeForPublish($event->reveal());
    }

    public function testHydrate(): void
    {
        $event = $this->prophesize(HydrateEvent::class);
        $document = $this->prophesize(LocalizedTimestampBehavior::class);
        $this->documentInspector->getOriginalLocale($document->reveal())->willReturn('de');

        $accessor = $this->prophesize(DocumentAccessor::class);
        $event->getAccessor()->willReturn($accessor->reveal());

        $event->getDocument()->willReturn($document->reveal());

        $this->propertyEncoder->encode('system_localized', 'created', 'de')->willReturn('i18n:de-created');
        $this->propertyEncoder->encode('system_localized', 'changed', 'de')->willReturn('i18n:de-changed');

        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertyValueWithDefault('i18n:de-created', null)->willReturn(new \DateTime());
        $node->getPropertyValueWithDefault('i18n:de-changed', null)->willReturn(new \DateTime());
        $event->getNode()->willReturn($node->reveal());

        $accessor->set('created', Argument::type(\DateTime::class))->shouldBeCalled();
        $accessor->set('changed', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->subscriber->setTimestampsOnDocument($event->reveal());
    }

    public function testHydrateWithoutLocalization(): void
    {
        $event = $this->prophesize(HydrateEvent::class);
        $document = $this->prophesize(TimestampBehavior::class);
        $this->documentInspector->getOriginalLocale($document->reveal())->willReturn('de');

        $accessor = $this->prophesize(DocumentAccessor::class);
        $event->getAccessor()->willReturn($accessor->reveal());

        $event->getDocument()->willReturn($document->reveal());

        $this->propertyEncoder->encode('system', 'created', 'de')->willReturn('created');
        $this->propertyEncoder->encode('system', 'changed', 'de')->willReturn('changed');

        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertyValueWithDefault('created', null)->willReturn(new \DateTime());
        $node->getPropertyValueWithDefault('changed', null)->willReturn(new \DateTime());
        $event->getNode()->willReturn($node->reveal());

        $accessor->set('created', Argument::type(\DateTime::class))->shouldBeCalled();
        $accessor->set('changed', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->subscriber->setTimestampsOnDocument($event->reveal());
    }

    public function testHydrateWithoutTimestampBehavior(): void
    {
        $event = $this->prophesize(HydrateEvent::class);
        $accessor = $this->prophesize(DocumentAccessor::class);
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getDocument()->willReturn(new \stdClass());

        $accessor->set(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->setTimestampsOnDocument($event->reveal());
    }

    public function testSetChangedForRestore(): void
    {
        $event = $this->prophesize(RestoreEvent::class);
        $document = $this->prophesize(LocalizedTimestampBehavior::class);
        $this->documentInspector->getOriginalLocale($document->reveal())->willReturn('de');

        $node = $this->prophesize(NodeInterface::class);
        $event->getNode()->willReturn($node->reveal());

        $event->getDocument()->willReturn($document->reveal());

        $this->propertyEncoder->encode('system_localized', 'changed', 'de')->willReturn('i18n:de-changed');

        $node->setProperty('i18n:de-changed', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->subscriber->setChangedForRestore($event->reveal());
    }

    public function testSetChangedForRestoreNonLocalized(): void
    {
        $event = $this->prophesize(RestoreEvent::class);
        $document = $this->prophesize(TimestampBehavior::class);
        $this->documentInspector->getOriginalLocale($document->reveal())->willReturn('de');

        $node = $this->prophesize(NodeInterface::class);
        $event->getNode()->willReturn($node->reveal());

        $event->getDocument()->willReturn($document->reveal());

        $this->propertyEncoder->encode('system', 'changed', 'de')->willReturn('changed');

        $node->setProperty('changed', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->subscriber->setChangedForRestore($event->reveal());
    }
}
