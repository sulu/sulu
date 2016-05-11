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

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Subscriber\SecuritySubscriber;

class SecuritySubscriberTest extends SubscriberTestCase
{
    /**
     * @var SecuritySubscriber
     */
    private $subscriber;

    public function setUp()
    {
        parent::setUp();

        $this->subscriber = new SecuritySubscriber(['view' => 64, 'add' => 32, 'edit' => 16, 'delete' => 8]);
    }

    public function testPersist()
    {
        /** @var SecurityBehavior $document */
        $document = $this->prophesize(SecurityBehavior::class);
        $document->getPermissions()->willReturn(
            [1 => ['view' => true, 'add' => true, 'edit' => true, 'delete' => false]]
        );

        $this->persistEvent->getDocument()->willReturn($document);

        $this->subscriber->handlePersist($this->persistEvent->reveal());

        $this->node->setProperty('sec:role-1', ['view', 'add', 'edit'])->shouldBeCalled();
    }

    public function testHydrate()
    {
        /** @var SecurityBehavior $document */
        $document = $this->prophesize(SecurityBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        /** @var PropertyInterface $roleProperty1 */
        $roleProperty1 = $this->prophesize(PropertyInterface::class);
        $roleProperty1->getName()->willReturn('sec:role-1');
        $roleProperty1->getValue()->willReturn(['view', 'add', 'edit']);

        /** @var PropertyInterface $roleProperty2 */
        $roleProperty2 = $this->prophesize(PropertyInterface::class);
        $roleProperty2->getName()->willReturn('sec:role-2');
        $roleProperty2->getValue()->willReturn(['view', 'edit']);

        $node->getProperties('sec:*')->willReturn([$roleProperty1->reveal(), $roleProperty2->reveal()]);

        $this->hydrateEvent->getDocument()->willReturn($document);
        $this->hydrateEvent->getNode()->willReturn($node);

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());

        $document->setPermissions([
            1 => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => false,
            ],
            2 => [
                'view' => true,
                'add' => false,
                'edit' => true,
                'delete' => false,
            ],
        ])->shouldBeCalled();
    }
}
