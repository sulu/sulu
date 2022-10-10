<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Tests\Unit\Application\Dispatcher;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcher;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\ActivityBundle\Tests\Application\Domain\Event\TestDomainEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DomainEventDispatcherTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<EventDispatcherInterface>
     */
    private $innerEventDispatcher;

    public function setUp(): void
    {
        $this->innerEventDispatcher = $this->prophesize(EventDispatcherInterface::class);
    }

    public function testDispatch(): void
    {
        $dispatcher = $this->createDomainEventDispatcher();

        $event = new TestDomainEvent();
        $this->innerEventDispatcher->dispatch($event, DomainEvent::class)
            ->shouldBeCalled()
            ->willReturn($event);

        $dispatcher->dispatch($event);
    }

    private function createDomainEventDispatcher(): DomainEventDispatcher
    {
        return new DomainEventDispatcher(
            $this->innerEventDispatcher->reveal()
        );
    }
}
