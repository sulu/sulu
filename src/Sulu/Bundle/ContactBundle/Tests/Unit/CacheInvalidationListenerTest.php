<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Unit;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\EventListener\CacheInvalidationListener;
use Sulu\Component\Contact\Model\ContactInterface;
use Sulu\Component\HttpCache\HandlerInvalidateReferenceInterface;

class CacheInvalidationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HandlerInvalidateReferenceInterface
     */
    private $invalidationHandler;

    /**
     * @var CacheInvalidationListener
     */
    private $listener;

    protected function setUp()
    {
        $this->invalidationHandler = $this->prophesize(HandlerInvalidateReferenceInterface::class);

        $this->listener = new CacheInvalidationListener($this->invalidationHandler->reveal());
    }

    public function provideDataPostPersist()
    {
        return [
            [ContactInterface::class, 'contact'],
            [AccountInterface::class, 'account'],
            [\stdClass::class, null],
        ];
    }

    /**
     * @dataProvider provideDataPostPersist
     */
    public function testPostPersist($class, $alias)
    {
        $entity = $this->prophesize($class);

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        if ($alias) {
            $entity->getId()->willReturn(1);
            $this->invalidationHandler->invalidateReference($alias, 1)->shouldBeCalled();
        }

        $this->listener->postPersist($eventArgs->reveal());
    }
}
