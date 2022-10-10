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
use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\RefreshEvent;
use Sulu\Component\DocumentManager\NodeHelperInterface;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\GeneralSubscriber;

class GeneralSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public const SRC_PATH = '/path/to';

    public const DST_PATH = '/dest/path';

    public const DST_NAME = 'foo';

    /**
     * @var ObjectProphecy<NodeManager>
     */
    private $nodeManager;

    /**
     * @var ObjectProphecy<DocumentRegistry>
     */
    private $documentRegistry;

    /**
     * @var ObjectProphecy<NodeHelperInterface>
     */
    private $nodeHelper;

    /**
     * @var ObjectProphecy<MoveEvent>
     */
    private $moveEvent;

    /**
     * @var ObjectProphecy<CopyEvent>
     */
    private $copyEvent;

    /**
     * @var ObjectProphecy<ClearEvent>
     */
    private $clearEvent;

    /**
     * @var ObjectProphecy<FlushEvent>
     */
    private $flushEvent;

    /**
     * @var ObjectProphecy<RefreshEvent>
     */
    private $refreshEvent;

    /**
     * @var \stdClass
     */
    private $document;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var GeneralSubscriber
     */
    private $generalSubscriber;

    public function setUp(): void
    {
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->nodeHelper = $this->prophesize(NodeHelperInterface::class);

        $this->moveEvent = $this->prophesize(MoveEvent::class);
        $this->copyEvent = $this->prophesize(CopyEvent::class);
        $this->clearEvent = $this->prophesize(ClearEvent::class);
        $this->flushEvent = $this->prophesize(FlushEvent::class);
        $this->refreshEvent = $this->prophesize(RefreshEvent::class);

        $this->document = new \stdClass();
        $this->node = $this->prophesize(NodeInterface::class);

        $this->generalSubscriber = new GeneralSubscriber(
            $this->documentRegistry->reveal(),
            $this->nodeManager->reveal(),
            $this->nodeHelper->reveal()
        );
    }

    /**
     * It should move a document.
     */
    public function testHandleMove(): void
    {
        $this->moveEvent->getDocument()->willReturn($this->document);
        $this->moveEvent->getDestId()->willReturn(self::DST_PATH);
        $this->moveEvent->getDestName()->willReturn(self::DST_NAME);

        $this->documentRegistry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
        $this->node->getPath()->willReturn(self::SRC_PATH);

        $this->nodeHelper->move($this->node->reveal(), self::DST_PATH, self::DST_NAME)->shouldBeCalled();

        $this->generalSubscriber->handleMove($this->moveEvent->reveal());
    }

    /**
     * It should copy a document.
     */
    public function testHandleCopy(): void
    {
        $this->copyEvent->getDocument()->willReturn($this->document);
        $this->copyEvent->getDestId()->willReturn(self::DST_PATH);
        $this->copyEvent->getDestName()->willReturn(self::DST_NAME);
        $this->documentRegistry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
        $this->node->getPath()->willReturn(self::SRC_PATH);
        $this->nodeHelper->copy($this->node->reveal(), self::DST_PATH, self::DST_NAME)->willReturn('foobar');
        $node = $this->prophesize(NodeInterface::class);
        $node->getPath()->willReturn('foobar');
        $this->nodeManager->find('foobar')->willReturn($node->reveal());
        $this->copyEvent->setCopiedNode($node->reveal())->shouldBeCalled();

        $this->generalSubscriber->handleCopy($this->copyEvent->reveal());
    }

    /**
     * It should clear/reset the PHPCR session.
     */
    public function testHandleClear(): void
    {
        $this->nodeManager->clear()->shouldBeCalled();
        $this->generalSubscriber->handleClear($this->clearEvent->reveal());
    }

    /**
     * It should save the PHPCR session.
     */
    public function testHandleFlush(): void
    {
        $this->nodeManager->save()->shouldBeCalled();
        $this->generalSubscriber->handleFlush($this->flushEvent->reveal());
    }
}
