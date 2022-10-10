<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Comonent\DocumentManager\Tests\Unit\Subscriber;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\NodeHelperInterface;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\ReorderSubscriber;

class ReorderSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<NodeHelperInterface>
     */
    private $nodeHelper;

    /**
     * @var ReorderSubscriber
     */
    private $reorderSubscriber;

    public function setUp(): void
    {
        $this->nodeHelper = $this->prophesize(NodeHelperInterface::class);
        $this->reorderSubscriber = new ReorderSubscriber(
            $this->nodeHelper->reveal()
        );
    }

    public function testHandleReorder(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $event = $this->prophesize(ReorderEvent::class);

        $event->getNode()->willReturn($node->reveal())->shouldBeCalled();
        $event->getDestId()->willReturn('uuid')->shouldBeCalled();

        $this->nodeHelper->reorder($node->reveal(), 'uuid');

        $this->reorderSubscriber->handleReorder($event->reveal());
    }
}
