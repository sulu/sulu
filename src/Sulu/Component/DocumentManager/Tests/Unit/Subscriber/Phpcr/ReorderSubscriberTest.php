<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Comonent\DocumentManager\Tests\Unit\Subscriber;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\NodeHelperInterface;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\ReorderSubscriber;

class ReorderSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NodeHelperInterface
     */
    private $nodeHelper;

    /**
     * @var ReorderSubscriber
     */
    private $reorderSubscriber;

    public function setUp()
    {
        $this->nodeHelper = $this->prophesize(NodeHelperInterface::class);
        $this->reorderSubscriber = new ReorderSubscriber(
            $this->nodeHelper->reveal()
        );
    }

    public function testHandleReorder()
    {
        $node = $this->prophesize(NodeInterface::class);
        $event = $this->prophesize(ReorderEvent::class);

        $event->getNode()->willReturn($node->reveal());
        $event->getDestId()->willReturn('uuid');

        $this->nodeHelper->reorder($node->reveal(), 'uuid');

        $this->reorderSubscriber->handleReorder($event->reveal());
    }
}
