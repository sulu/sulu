<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\CustomUrl\Tests\Unit\Application\MessageHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\CustomUrl\Application\MessageHandler\RemoveCustomUrlMessageHandler;
use Sulu\CustomUrl\Application\Messages\RemoveCustomUrlMessage;
use Sulu\CustomUrl\Domain\Event\CustomUrlRemovedEvent;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRepositoryInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

class RemoveCustomUrlMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<CustomUrlRepositoryInterface> */
    private ObjectProphecy $customUrlRepository;

    /** @var ObjectProphecy<TrashManagerInterface> */
    private ObjectProphecy $trashManager;

    /** @var ObjectProphecy<DomainEventCollectorInterface> */
    private ObjectProphecy $domainEventCollector;

    private RemoveCustomUrlMessageHandler $handler;

    protected function setUp(): void
    {
        $this->customUrlRepository = $this->prophesize(CustomUrlRepositoryInterface::class);
        $this->trashManager = $this->prophesize(TrashManagerInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $this->handler = new RemoveCustomUrlMessageHandler(
            $this->customUrlRepository->reveal(),
            $this->trashManager->reveal(),
            $this->domainEventCollector->reveal()
        );
    }

    public function testRemoveCustomUrl(): void
    {
        $uuid = Uuid::v4()->toRfc4122();

        $customUrl = $this->prophesize(CustomUrlInterface::class);
        $customUrl->getWebspace()->willReturn('sulu_io');

        $this->customUrlRepository->getOneBy(['uuid' => $uuid])
            ->shouldBeCalledOnce()
            ->willReturn($customUrl->reveal());

        $this->trashManager->store(CustomUrlInterface::RESOURCE_KEY, $customUrl->reveal())
            ->shouldBeCalledOnce();

        $this->domainEventCollector->collect(Argument::that(function($event) {
            return $event instanceof CustomUrlRemovedEvent;
        }))->shouldBeCalledOnce();

        $this->customUrlRepository->remove($customUrl->reveal())
            ->shouldBeCalledOnce();

        $message = new RemoveCustomUrlMessage($uuid, 'sulu_io');

        $this->handler->__invoke($message);
    }

    public function testRemoveCustomUrlWithWrongWebspace(): void
    {
        $uuid = Uuid::v4()->toRfc4122();

        $customUrl = $this->prophesize(CustomUrlInterface::class);
        $customUrl->getWebspace()->willReturn('sulu_io');

        $this->customUrlRepository->getOneBy(['uuid' => $uuid])
            ->shouldBeCalledOnce()
            ->willReturn($customUrl->reveal());

        // Should NOT call trash manager, event collector, or repository remove
        $this->trashManager->store(Argument::any(), Argument::any())
            ->shouldNotBeCalled();
        $this->domainEventCollector->collect(Argument::any())
            ->shouldNotBeCalled();
        $this->customUrlRepository->remove(Argument::any())
            ->shouldNotBeCalled();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Entity from webspace "sulu_io" does not belong to webspace "wrong_webspace"');

        $message = new RemoveCustomUrlMessage($uuid, 'wrong_webspace');

        $this->handler->__invoke($message);
    }

    public function testRemoveCustomUrlStoresInTrash(): void
    {
        $uuid = Uuid::v4()->toRfc4122();

        $customUrl = $this->prophesize(CustomUrlInterface::class);
        $customUrl->getWebspace()->willReturn('sulu_io');

        $this->customUrlRepository->getOneBy(['uuid' => $uuid])
            ->shouldBeCalledOnce()
            ->willReturn($customUrl->reveal());

        // Verify trash storage happens before removal
        $this->trashManager->store(CustomUrlInterface::RESOURCE_KEY, $customUrl->reveal())
            ->shouldBeCalledOnce();

        $this->domainEventCollector->collect(Argument::type(CustomUrlRemovedEvent::class))
            ->shouldBeCalledOnce();

        $this->customUrlRepository->remove($customUrl->reveal())
            ->shouldBeCalledOnce();

        $message = new RemoveCustomUrlMessage($uuid, 'sulu_io');

        $this->handler->__invoke($message);
    }

    public function testRemoveCustomUrlCollectsEvent(): void
    {
        $uuid = Uuid::v4()->toRfc4122();

        $customUrl = $this->prophesize(CustomUrlInterface::class);
        $customUrl->getWebspace()->willReturn('sulu_io');

        $this->customUrlRepository->getOneBy(['uuid' => $uuid])
            ->shouldBeCalledOnce()
            ->willReturn($customUrl->reveal());

        $this->trashManager->store(CustomUrlInterface::RESOURCE_KEY, $customUrl->reveal())
            ->shouldBeCalledOnce();

        // Verify event is collected
        $eventCollected = false;
        $this->domainEventCollector->collect(Argument::that(function($event) use (&$eventCollected) {
            if ($event instanceof CustomUrlRemovedEvent) {
                $eventCollected = true;

                return true;
            }

            return false;
        }))->shouldBeCalledOnce();

        $this->customUrlRepository->remove($customUrl->reveal())
            ->shouldBeCalledOnce();

        $message = new RemoveCustomUrlMessage($uuid, 'sulu_io');

        $this->handler->__invoke($message);

        $this->assertTrue($eventCollected, 'CustomUrlRemovedEvent should have been collected');
    }
}
