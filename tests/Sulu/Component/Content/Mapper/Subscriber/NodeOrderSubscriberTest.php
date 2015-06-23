<?php

namespace Sulu\Component\Content\Mapper\Subscriber;

use PHPCR\PropertyType;
use Prophecy\Argument;
use Sulu\Component\Content\ContentEvents;
use Sulu\Component\Content\Event\ContentNodeEvent;
use Sulu\Component\Content\Event\ContentNodeOrderEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class NodeOrderSubscriberTest extends \PHPUnit_Framework_TestCase
{
    protected $subscriber;

    public function setUp()
    {
        parent::setUp();
        $this->node = $this->prophesize('PHPCR\NodeInterface');
        $this->orderBefore = $this->prophesize('PHPCR\NodeInterface');
        $this->node->getName()->willReturn('Node 1');
        $this->parent = $this->prophesize('PHPCR\NodeInterface');
        $this->structure = $this->prophesize('Sulu\Component\Content\Structure');

        for ($i = 0; $i <= 3; $i++) {
            $this->{'sibling' . $i} = $this->prophesize('PHPCR\NodeInterface');
            $this->{'sibling' . $i}->getName()->willReturn('Sibling 1');
            $this->{'sibling' . $i}->setProperty('sulu:order', Argument::any(), Argument::any())->willReturn('Sibling 1');
        }

        $this->structure = $this->prophesize('Sulu\Component\Content\Structure');

        $this->subscriber = new NodeOrderSubscriber();
        $this->nodeOrderEvent = new ContentNodeOrderEvent($this->node->reveal(), $this->orderBefore->reveal());
        $this->nodeSaveEvent = new ContentNodeEvent($this->node->reveal(), $this->structure->reveal());
        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addSubscriber($this->subscriber);
    }

    public function testDispatchNodeOrder()
    {
        $this->node->getParent()->willReturn($this->parent->reveal());
        $this->parent->getNodes()->willReturn(array(
            $this->sibling1,
            $this->sibling2,
            $this->node,
            $this->sibling3,
        ));

        $this->node->setProperty('sulu:order', 30, PropertyType::LONG)->shouldBeCalled();

        $this->eventDispatcher->dispatch(ContentEvents::NODE_ORDER, $this->nodeOrderEvent);
    }

    public function testDispatchNodeSave()
    {
        $this->node->getParent()->willReturn($this->parent->reveal());
        $this->parent->getNodes()->willReturn(array(
            $this->sibling1,
            $this->sibling2,
        ));

        $this->node->hasProperty('sulu:order')->willReturn(false);
        $this->node->setProperty('sulu:order', 30, PropertyType::LONG)->shouldBeCalled();

        $this->eventDispatcher->dispatch(ContentEvents::NODE_PRE_SAVE, $this->nodeSaveEvent);
    }

    public function testDispatchNodeSaveExistingOrder()
    {
        $this->node->hasProperty('sulu:order')->willReturn(true);
        $this->node->setProperty()->shouldNotBeCalled();

        $this->eventDispatcher->dispatch(ContentEvents::NODE_PRE_SAVE, $this->nodeSaveEvent);
    }
}
