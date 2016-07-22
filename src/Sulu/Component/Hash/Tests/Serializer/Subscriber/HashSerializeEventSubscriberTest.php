<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Hash\Tests\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\GenericSerializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\XmlSerializationVisitor;
use Prophecy\Argument;
use Sulu\Component\Hash\HasherInterface;
use Sulu\Component\Hash\Serializer\Subscriber\HashSerializeEventSubscriber;
use Sulu\Component\Persistence\Model\AuditableInterface;

class HashSerializeEventSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HasherInterface
     */
    private $hasher;

    /**
     * @var HashSerializeEventSubscriber
     */
    private $hashSerializeEventSubscriber;

    /**
     * @var GenericSerializationVisitor
     */
    private $visitor;

    /**
     * @var ObjectEvent
     */
    private $objectEvent;

    public function setUp()
    {
        $this->hasher = $this->prophesize(HasherInterface::class);
        $this->visitor = $this->prophesize(JsonSerializationVisitor::class);
        $this->hashSerializeEventSubscriber = new HashSerializeEventSubscriber($this->hasher->reveal());
        $this->objectEvent = $this->prophesize(ObjectEvent::class);
        $this->objectEvent->getVisitor()->willReturn($this->visitor->reveal());
    }

    public function testOnPostSerialize()
    {
        $object = $this->prophesize(AuditableInterface::class);
        $this->objectEvent->getObject()->willReturn($object);

        $this->visitor->addData('_hash', Argument::any())->shouldBeCalled();
        $this->hashSerializeEventSubscriber->onPostSerialize($this->objectEvent->reveal());
    }

    public function testOnPostSerializeWithWrongObject()
    {
        $object = new \stdClass();
        $this->objectEvent->getObject()->willReturn($object);

        $this->visitor->addData('_hash', Argument::any())->shouldNotBeCalled();
        $this->hashSerializeEventSubscriber->onPostSerialize($this->objectEvent->reveal());
    }

    public function testOnNonGenericSerialization()
    {
        $xmlVisitor = $this->prophesize(XmlSerializationVisitor::class);
        $object = $this->prophesize(AuditableInterface::class);
        $this->objectEvent->getObject()->willReturn($object);
        $this->objectEvent->getVisitor()->willReturn($xmlVisitor->reveal());

        $this->hasher->hash()->shouldNotBeCalled();

        $this->hashSerializeEventSubscriber->onPostSerialize($this->objectEvent->reveal());
    }
}
