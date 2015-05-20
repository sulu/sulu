<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\EventListener;

use Sulu\Component\Content\Event\ContentNodeDeleteEvent;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\HttpCache\EventSubscriber\ContentMapperSubscriber;
use Sulu\Component\HttpCache\HandlerInterface;

class ContentMapperSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentMapperSubscriber
     */
    private $subscriber;

    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var StructureInterface
     */
    private $structure;

    /**
     * @var ContentNodeDeleteEvent
     */
    private $event;

    public function setUp()
    {
        parent::setUp();

        $this->nodeEvent = $this->prophesize('Sulu\Component\Content\Event\ContentNodeEvent');
        $this->deleteEvent = $this->prophesize('Sulu\Component\Content\Event\ContentNodeDeleteEvent');
        $this->structure = $this->prophesize('Sulu\Component\Content\StructureInterface');
        $this->handler = $this->prophesize('Sulu\Component\HttpCache\HandlerInvalidateStructureInterface');

        $this->subscriber = new ContentMapperSubscriber(
            $this->handler->reveal()
        );

        ContentMapperSubscriber::getSubscribedEvents();
    }

    public function testNodeSave()
    {
        $this->nodeEvent->getStructure()->willReturn($this->structure);
        $this->handler->invalidateStructure($this->structure->reveal())->shouldBeCalled();
        $this->subscriber->onContentNodePostSave($this->nodeEvent->reveal());
    }

    public function testNodeDelete()
    {
        $this->deleteEvent->getStructures()->willReturn(array($this->structure));
        $this->handler->invalidateStructure($this->structure->reveal())->shouldBeCalled();
        $this->subscriber->onContentNodePreDelete($this->deleteEvent->reveal());
        $this->subscriber->onContentNodePostDelete($this->deleteEvent->reveal());
    }
}
