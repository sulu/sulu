<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Search\EventSubscriber;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Tests\Unit\Document\Subscriber\SubscriberTestCase;
use Sulu\Component\DocumentManager\Event\RemoveEvent;

class StructureSubscriberTest extends SubscriberTestCase
{
    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    /**
     * @var StructureSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        parent::setUp();

        $this->searchManager = $this->prophesize(SearchManagerInterface::class);
        $this->subscriber = new StructureSubscriber($this->searchManager->reveal());
    }

    public function testHandlePersist()
    {
        $document = $this->prophesize(StructureBehavior::class);
        $this->persistEvent->getDocument()->willReturn($document->reveal());

        $this->searchManager->index($document)->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testHandlePersistUnsecuredDocument()
    {
        $document = $this->prophesize(StructureBehavior::class);
        $document->willImplement(SecurityBehavior::class);
        $document->getPermissions()->willReturn([]);

        $this->persistEvent->getDocument()->willReturn($document->reveal());

        $this->searchManager->index($document)->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testHandlePersistSecuredDocument()
    {
        $document = $this->prophesize(StructureBehavior::class);
        $document->willImplement(SecurityBehavior::class);
        $document->getPermissions()->willReturn(['some' => 'permissions']);

        $this->persistEvent->getDocument()->willReturn($document);

        $this->searchManager->index($document)->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testHandlePreRemove()
    {
        $removeEvent = $this->prophesize(RemoveEvent::class);

        $document = $this->prophesize(StructureBehavior::class);
        $removeEvent->getDocument()->willReturn($document);

        $this->searchManager->deindex($document)->shouldBeCalled();

        $this->subscriber->handlePreRemove($removeEvent->reveal());
    }
}
