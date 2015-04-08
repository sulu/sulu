<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\PhpcrOdm\EventSubscriber;

use Prophecy\PhpUnit\ProphecyTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use DTL\Component\Content\Document\DocumentInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Prophecy\Argument;
use Sulu\Component\Security\Authentication\UserInterface;

class TimestampSubscriberTest extends ProphecyTestCase
{
    private $subscriber;
    private $event;
    private $document;

    public function setUp()
    {
        $this->event = $this->prophesize(LifecycleEventArgs::class);
        $this->document = $this->prophesize(DocumentInterface::class);

        $this->subscriber = new TimestampSubscriber();
    }

    public function testNoDocument()
    {
        $this->document->setCreated(Argument::any())->shouldNotBeCalled();
        $this->document->setChanged(Argument::any())->shouldNotBeCalled();
        $this->event->getObject()->willReturn(new \stdClass);
        $this->subscriber->prePersist($this->event->reveal());
    }

    public function testNoCreated()
    {
        $this->document->setCreated(Argument::type('DateTime'))->shouldBeCalled();
        $this->document->setChanged(Argument::type('DateTime'))->shouldBeCalled();
        $this->document->getCreated()->willReturn(null);
        $this->event->getObject()->willReturn($this->document->reveal());
        $this->subscriber->prePersist($this->event->reveal());
    }

    public function testExistingCreated()
    {
        $this->document->setCreated(Argument::type('DateTime'))->shouldNotBeCalled();
        $this->document->setChanged(Argument::type('DateTime'))->shouldBeCalled();
        $this->document->getCreated()->willReturn(new \DateTime());
        $this->event->getObject()->willReturn($this->document->reveal());
        $this->subscriber->prePersist($this->event->reveal());
    }
}
