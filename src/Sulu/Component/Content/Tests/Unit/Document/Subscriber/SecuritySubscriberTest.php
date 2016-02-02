<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Subscriber\SecuritySubscriber;
use Sulu\Component\DocumentManager\Event\PersistEvent;

class SecuritySubscriberTest extends SubscriberTestCase
{
    /**
     * @var SecuritySubscriber
     */
    private $subscriber;

    public function setUp()
    {
        parent::setUp();

        $this->subscriber = new SecuritySubscriber();
    }

    public function testPersist()
    {
        /** @var SecurityBehavior $document */
        $document = $this->prophesize(SecurityBehavior::class);
        $document->getPermissions()->willReturn(
            [1 => ['view', 'add', 'edit']]
        );

        $this->persistEvent->getDocument()->willReturn($document);

        $this->subscriber->handlePersist($this->persistEvent->reveal());

        $this->node->setProperty('sec:role-1', ['view', 'add', 'edit'])->shouldBeCalled();
    }
}
