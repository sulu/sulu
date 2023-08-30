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
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\RemoveSubscriber;

class RemoveSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<NodeManager>
     */
    private $nodeManager;

    /**
     * @var ObjectProphecy<DocumentRegistry>
     */
    private $documentRegistry;

    /**
     * @var ObjectProphecy<RemoveEvent>
     */
    private $removeEvent;

    /**
     * @var object
     */
    private $document;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var RemoveSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->removeEvent = $this->prophesize(RemoveEvent::class);
        $this->document = new \stdClass();
        $this->node = $this->prophesize(NodeInterface::class);

        $this->subscriber = new RemoveSubscriber(
            $this->documentRegistry->reveal(),
            $this->nodeManager->reveal()
        );

        $this->documentRegistry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
    }

    /**
     * It should remove nodes from the PHPCR session.
     */
    public function testHandleRemove(): void
    {
        $this->removeEvent->getDocument()->willReturn($this->document);
        $this->node->remove()->shouldBeCalled();

        $this->subscriber->handleRemove($this->removeEvent->reveal());
    }
}
