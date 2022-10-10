<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;

class SubscriberTestCase extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<PersistEvent>
     */
    protected $persistEvent;

    /**
     * @var ObjectProphecy<HydrateEvent>
     */
    protected $hydrateEvent;

    /**
     * @var \stdClass
     */
    protected $notImplementing;

    /**
     * @var ObjectProphecy<PropertyEncoder>
     */
    protected $encoder;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    protected $node;

    /**
     * @var ObjectProphecy<DocumentAccessor>
     */
    protected $accessor;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    protected $parentNode;

    public function setUp(): void
    {
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->notImplementing = new \stdClass();
        $this->encoder = $this->prophesize(PropertyEncoder::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->accessor = $this->prophesize(DocumentAccessor::class);
        $this->persistEvent->getNode()->willReturn($this->node);
        $this->persistEvent->getAccessor()->willReturn($this->accessor);
        $this->hydrateEvent->getAccessor()->willReturn($this->accessor);
    }
}
