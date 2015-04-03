<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Comonent\DocumentManager\Tests\Unit\Subscriber;

use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\GeneralSubscriber;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;

class GeneralSubscriberTest extends \PHPUnit_Framework_TestCase
{
    const SRC_PATH = '/path/to';
    const DST_PATH = '/dest/path';

    public function setUp()
    {
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);

        $this->removeEvent = $this->prophesize(RemoveEvent::class);
        $this->moveEvent = $this->prophesize(MoveEvent::class);
        $this->copyEvent = $this->prophesize(CopyEvent::class);
        $this->clearEvent = $this->prophesize(ClearEvent::class);
        $this->flushEvent = $this->prophesize(FlushEvent::class);

        $this->document = new \stdClass;
        $this->node = $this->prophesize(NodeInterface::class);

        $this->subscriber = new GeneralSubscriber(
            $this->documentRegistry->reveal(),
            $this->nodeManager->reveal()
        );
    }

    /**
     * It should remove nodes from the PHPCR session and deregister the
     * given document,
     */
    public function testHandleRemove()
    {
        $this->removeEvent->getDocument()->willReturn($this->document);
        $this->documentRegistry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
        $this->node->remove()->shouldBeCalled();
        $this->documentRegistry->deregisterDocument($this->document)->shouldBeCalled();

        $this->subscriber->handleRemove($this->removeEvent->reveal());
    }

    /**
     * It should move a document
     */
    public function testHandleMove()
    {
        $this->moveEvent->getDocument()->willReturn($this->document);
        $this->moveEvent->getDestId()->willReturn(self::DST_PATH);
        $this->documentRegistry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
        $this->node->getPath()->willReturn(self::SRC_PATH);
        $this->nodeManager->move(self::SRC_PATH, self::DST_PATH)->shouldBeCalled();

        $this->subscriber->handleMove($this->moveEvent->reveal());
    }

    /**
     * It should copy a document
     */
    public function testHandleCopy()
    {
        $this->copyEvent->getDocument()->willReturn($this->document);
        $this->copyEvent->getDestId()->willReturn(self::DST_PATH);
        $this->documentRegistry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
        $this->node->getPath()->willReturn(self::SRC_PATH);
        $this->nodeManager->copy(self::SRC_PATH, self::DST_PATH)->shouldBeCalled();

        $this->subscriber->handleCopy($this->copyEvent->reveal());
    }

    /**
     * It should clear/reset the PHPCR session
     */
    public function testHandleClear()
    {
        $this->nodeManager->clear()->shouldBeCalled();
        $this->subscriber->handleClear($this->clearEvent->reveal());
    }

    /**
     * It should save the PHPCR session
     */
    public function testHandleFlush()
    {
        $this->nodeManager->save()->shouldBeCalled();
        $this->subscriber->handleFlush($this->flushEvent->reveal());
    }
}
