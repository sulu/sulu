<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior;

use Sulu\Component\DocumentManager\Subscriber\Behavior\TimestampSubscriber;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Behavior\TimestampBehavior;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Subscriber\Behavior\NodeNameSubscriber;
use Sulu\Component\DocumentManager\Behavior\NodeNameBehavior;

class NodeNameSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->notImplementing = new \stdClass;
        $this->node = $this->prophesize(NodeInterface::class);
        $this->document = new TestNodeNameDocument();
        $this->accessor = new DocumentAccessor($this->document);

        $this->subscriber = new NodeNameSubscriber();
    }

    /**
     * It should return early when not implementing
     */
    public function testHydrateNotImplementing()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->notImplementing);
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should set the node name on the document
     */
    public function testHydrate()
    {
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getDocument()->willReturn($this->document);
        $this->hydrateEvent->getAccessor()->willReturn($this->accessor);
        $this->node->getName()->willReturn('hello');

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());

        $this->assertEquals('hello', $this->document->getNodeName());
    }

}

class TestNodeNameDocument implements NodeNameBehavior
{
    private $nodeName;

    public function getNodeName()
    {
        return $this->nodeName;
    }
}
