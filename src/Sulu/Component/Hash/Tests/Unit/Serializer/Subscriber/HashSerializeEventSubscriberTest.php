<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Hash\Tests\Unit\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Hash\HasherInterface;
use Sulu\Component\Hash\Serializer\Subscriber\HashSerializeEventSubscriber;
use Sulu\Component\Persistence\Model\AuditableInterface;

class HashSerializeEventSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<HasherInterface>
     */
    private $hasher;

    /**
     * @var HashSerializeEventSubscriber
     */
    private $hashSerializeEventSubscriber;

    /**
     * @var ObjectProphecy<SerializationVisitorInterface>
     */
    private $visitor;

    /**
     * @var ObjectProphecy<ObjectEvent>
     */
    private $objectEvent;

    public function setUp(): void
    {
        $this->hasher = $this->prophesize(HasherInterface::class);
        $this->visitor = $this->prophesize(SerializationVisitorInterface::class);
        $this->hashSerializeEventSubscriber = new HashSerializeEventSubscriber($this->hasher->reveal());
        $this->objectEvent = $this->prophesize(ObjectEvent::class);
        $this->objectEvent->getVisitor()->willReturn($this->visitor->reveal());
    }

    public function testOnPostSerialize(): void
    {
        $object = $this->prophesize(AuditableInterface::class);
        $this->objectEvent->getObject()->willReturn($object);

        $this->visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return '_hash' === $metadata->name;
        }), Argument::any())->shouldBeCalled();
        $this->hashSerializeEventSubscriber->onPostSerialize($this->objectEvent->reveal());
    }

    public function testOnPostSerializeWithWrongObject(): void
    {
        $object = new \stdClass();
        $this->objectEvent->getObject()->willReturn($object);

        $this->visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return '_hash' === $metadata->name;
        }), Argument::any())->shouldNotBeCalled();
        $this->hashSerializeEventSubscriber->onPostSerialize($this->objectEvent->reveal());
    }

    public function testOnNonSerializationVisitor(): void
    {
        $xmlVisitor = $this->prophesize(DeserializationVisitorInterface::class);
        $object = $this->prophesize(AuditableInterface::class);
        $this->objectEvent->getObject()->willReturn($object);
        $this->objectEvent->getVisitor()->willReturn($xmlVisitor->reveal());

        $this->visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return '_hash' === $metadata->name;
        }), Argument::any())->shouldNotBeCalled();

        $this->hashSerializeEventSubscriber->onPostSerialize($this->objectEvent->reveal());
    }
}
