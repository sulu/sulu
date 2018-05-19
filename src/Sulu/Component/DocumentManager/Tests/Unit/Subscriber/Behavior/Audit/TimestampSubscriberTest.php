<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\Audit;

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Behavior\Audit\LocalizedTimestampBehavior;
use Sulu\Component\DocumentManager\Behavior\Audit\TimestampBehavior;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Audit\TimestampSubscriber;

class TimestampSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var TimestampSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->subscriber = new TimestampSubscriber($this->propertyEncoder->reveal());
    }

    public function testSetTimestampsOnNodeForPersistNotImplementing()
    {
        $event = $this->prophesize(PersistEvent::class);
        $event->getDocument()->willReturn(new \stdClass());
        $this->subscriber->setTimestampsOnNodeForPersist($event->reveal());
    }

    public function testSetTimestampsOnNodeForPersistCreatedWhenNull()
    {
        $event = $this->prophesize(PersistEvent::class);
        $accessor = $this->prophesize(DocumentAccessor::class);
        $document = $this->prophesize(LocalizedTimestampBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

        $this->propertyEncoder->encode('system_localized', 'created', 'de')->willReturn('i18n:de-created');
        $this->propertyEncoder->encode('system_localized', 'changed', 'de')->willReturn('i18n:de-changed');

        $node->hasProperty('i18n:de-created')->willReturn(false);
        $accessor->set('created', Argument::type(\DateTime::class))->shouldBeCalled();
        $node->setProperty('i18n:de-created', Argument::type(\DateTime::class))->shouldBeCalled();

        $accessor->set('changed', Argument::type(\DateTime::class))->shouldBeCalled();
        $node->setProperty('i18n:de-changed', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->subscriber->setTimestampsOnNodeForPersist($event->reveal());
    }

    public function testSetTimestampsOnNodeForPersistCreatedWhenSet()
    {
        $event = $this->prophesize(PersistEvent::class);
        $accessor = $this->prophesize(DocumentAccessor::class);
        $document = $this->prophesize(LocalizedTimestampBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

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

    public function testSetTimestampsOnNodeForPersistChanged()
    {
        $event = $this->prophesize(PersistEvent::class);
        $accessor = $this->prophesize(DocumentAccessor::class);
        $document = $this->prophesize(LocalizedTimestampBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

        $this->propertyEncoder->encode('system_localized', 'created', 'de')->willReturn('i18n:de-created');
        $node->hasProperty('i18n:de-created')->willReturn(true);
        $accessor->set('created', Argument::type(\DateTime::class))->shouldNotBeCalled();
        $node->setProperty('i18n:de-created', Argument::type(\DateTime::class))->shouldNotBeCalled();

        $this->propertyEncoder->encode('system_localized', 'changed', 'de')->willReturn('i18n:de-changed');
        $accessor->set('changed', Argument::type('DateTime'))->shouldBeCalled();
        $node->setProperty('i18n:de-changed', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->subscriber->setTimestampsOnNodeForPersist($event->reveal());
    }

    public function testSetTimestampsOnNodeForPersistNonLocalized()
    {
        $event = $this->prophesize(PersistEvent::class);
        $accessor = $this->prophesize(DocumentAccessor::class);
        $document = $this->prophesize(TimestampBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

        $this->propertyEncoder->encode('system', 'created', 'de')->willReturn('created');
        $this->propertyEncoder->encode('system', 'changed', 'de')->willReturn('changed');

        $node->hasProperty('created')->willReturn(false);
        $accessor->set('created', Argument::type(\DateTime::class))->shouldBeCalled();
        $node->setProperty('created', Argument::type(\DateTime::class))->shouldBeCalled();

        $accessor->set('changed', Argument::type(\DateTime::class))->shouldBeCalled();
        $node->setProperty('changed', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->subscriber->setTimestampsOnNodeForPersist($event->reveal());
    }

    public function testSetTimestampOnNodeForPersistLocalizedWithoutLocale()
    {
        $event = $this->prophesize(PersistEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(LocalizedTimestampBehavior::class);
        $accessor = $this->prophesize(DocumentAccessor::class);

        $event->getNode()->willReturn($node->reveal());
        $event->getDocument()->willReturn($document->reveal());
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getLocale()->willReturn(null);

        $this->propertyEncoder->encode(Argument::cetera())->shouldNotBeCalled();
        $node->setProperty(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->setTimestampsOnNodeForPersist($event->reveal());
    }

    public function testSetTimestampsOnNodeForPublishNotImplementing()
    {
        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn(new \stdClass());
        $this->subscriber->setTimestampsOnNodeForPublish($event->reveal());
    }

    public function testSetTimestampsOnNodeForPublishCreatedWhenNull()
    {
        $event = $this->prophesize(PublishEvent::class);
        $accessor = $this->prophesize(DocumentAccessor::class);
        $document = $this->prophesize(LocalizedTimestampBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

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

    public function testSetTimestampsOnNodeForPublishChanged()
    {
        $event = $this->prophesize(PublishEvent::class);
        $accessor = $this->prophesize(DocumentAccessor::class);
        $document = $this->prophesize(LocalizedTimestampBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

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

    public function testSetTimestampsOnNodeForPublishNonLocalized()
    {
        $event = $this->prophesize(PublishEvent::class);
        $accessor = $this->prophesize(DocumentAccessor::class);
        $document = $this->prophesize(TimestampBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

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

    public function testHydrate()
    {
        $event = $this->prophesize(HydrateEvent::class);
        $event->getLocale()->willReturn('de');

        $accessor = $this->prophesize(DocumentAccessor::class);
        $event->getAccessor()->willReturn($accessor->reveal());

        $document = $this->prophesize(LocalizedTimestampBehavior::class);
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

    public function testHydrateWithoutLocalization()
    {
        $event = $this->prophesize(HydrateEvent::class);
        $event->getLocale()->willReturn('de');

        $accessor = $this->prophesize(DocumentAccessor::class);
        $event->getAccessor()->willReturn($accessor->reveal());

        $document = $this->prophesize(TimestampBehavior::class);
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

    public function testHydrateWithoutTimestampBehavior()
    {
        $event = $this->prophesize(HydrateEvent::class);
        $accessor = $this->prophesize(DocumentAccessor::class);
        $event->getAccessor()->willReturn($accessor->reveal());
        $event->getDocument()->willReturn(new \stdClass());

        $accessor->set(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->setTimestampsOnDocument($event->reveal());
    }

    public function testSetChangedForRestore()
    {
        $event = $this->prophesize(RestoreEvent::class);
        $event->getLocale()->willReturn('de');

        $node = $this->prophesize(NodeInterface::class);
        $event->getNode()->willReturn($node->reveal());

        $document = $this->prophesize(LocalizedTimestampBehavior::class);
        $event->getDocument()->willReturn($document->reveal());

        $this->propertyEncoder->encode('system_localized', 'changed', 'de')->willReturn('i18n:de-changed');

        $node->setProperty('i18n:de-changed', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->subscriber->setChangedForRestore($event->reveal());
    }

    public function testSetChangedForRestoreNonLocalized()
    {
        $event = $this->prophesize(RestoreEvent::class);
        $event->getLocale()->willReturn('de');

        $node = $this->prophesize(NodeInterface::class);
        $event->getNode()->willReturn($node->reveal());

        $document = $this->prophesize(TimestampBehavior::class);
        $event->getDocument()->willReturn($document->reveal());

        $this->propertyEncoder->encode('system', 'changed', 'de')->willReturn('changed');

        $node->setProperty('changed', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->subscriber->setChangedForRestore($event->reveal());
    }
}
