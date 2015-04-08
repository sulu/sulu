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
use DTL\Component\Content\PhpcrOdm\EventSubscriber\DocumentNodeHelper;

class DocumentNodeHelperSubscriberTest extends ProphecyTestCase
{
    private $subscriber;
    private $helper;
    private $document;

    public function setUp()
    {
        $this->helper = $this->prophesize('DTL\Component\Content\PhpcrOdm\DocumentNodeHelper');
        $this->document = $this->prophesize('DTL\Component\Content\Document\DocumentInterface');
        $this->event = $this->prophesize('Doctrine\ORM\Event\LifecycleEventArgs');
        $this->subscriber = new DocumentNodeHelperSubscriber($this->helper->reveal());
    }

    public function testNotDocument()
    {
        $this->document->setDocumentNodeHelper()->shouldNotBeCalled();
        $this->event->getObject()->willReturn(new \stdClass);
        $this->subscriber->postLoad($this->event->reveal());
    }

    public function testSetNamepsaceRegistry()
    {
        $this->document->setDocumentNodeHelper($this->helper->reveal())->shouldBeCalled();
        $this->event->getObject()->willReturn($this->document->reveal());
        $this->subscriber->postLoad($this->event->reveal());
    }
}

