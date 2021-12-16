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

namespace Sulu\Bundle\TrashBundle\Tests\Unit\Application\TrashManager;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RemoveTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManager;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Bundle\TrashBundle\Domain\Event\TrashItemRemovedEvent;
use Sulu\Bundle\TrashBundle\Domain\Exception\RestoreTrashItemHandlerNotFoundException;
use Sulu\Bundle\TrashBundle\Domain\Exception\StoreTrashItemHandlerNotFoundException;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItem;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

class TrashManagerTest extends TestCase
{
    /**
     * @var ObjectProphecy<TrashItemRepositoryInterface>
     */
    private $trashItemRepository;

    /**
     * @var ObjectProphecy<DomainEventCollectorInterface>
     */
    private $domainEventCollector;

    /**
     * @var ObjectProphecy<ServiceLocator>
     */
    private $storeTrashItemHandlerLocator;

    /**
     * @var ObjectProphecy<ServiceLocator>
     */
    private $restoreTrashItemHandlerLocator;

    /**
     * @var ObjectProphecy<ServiceLocator>
     */
    private $removeTrashItemHandlerLocator;

    /**
     * @var TrashManagerInterface
     */
    private $trashManager;

    public function setUp(): void
    {
        $this->trashItemRepository = $this->prophesize(TrashItemRepositoryInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);
        $this->storeTrashItemHandlerLocator = $this->prophesize(ServiceLocator::class);
        $this->restoreTrashItemHandlerLocator = $this->prophesize(ServiceLocator::class);
        $this->removeTrashItemHandlerLocator = $this->prophesize(ServiceLocator::class);

        $this->trashManager = new TrashManager(
            $this->trashItemRepository->reveal(),
            $this->domainEventCollector->reveal(),
            $this->storeTrashItemHandlerLocator->reveal(),
            $this->restoreTrashItemHandlerLocator->reveal(),
            $this->removeTrashItemHandlerLocator->reveal()
        );
    }

    public function testStore(): void
    {
        $storeTrashItemHandler = $this->prophesize(StoreTrashItemHandlerInterface::class);

        $this->storeTrashItemHandlerLocator->has('tags')->willReturn(true);
        $this->storeTrashItemHandlerLocator->get('tags')->willReturn($storeTrashItemHandler->reveal());

        $object = new \stdClass();
        $trashItem = new TrashItem();
        $options = [];

        $storeTrashItemHandler->store($object, $options)->shouldBeCalled()->willReturn($trashItem);
        $this->trashItemRepository->add($trashItem)->shouldBeCalled();

        $result = $this->trashManager->store('tags', $object, $options);

        static::assertSame($trashItem, $result);
    }

    public function testStoreTrashItemHandlerNotFound(): void
    {
        static::expectException(StoreTrashItemHandlerNotFoundException::class);

        $this->storeTrashItemHandlerLocator->has('tags')->willReturn(false);

        $this->trashManager->store('tags', new \stdClass());
    }

    public function testRestore(): void
    {
        $restoreTrashItemHandler = $this->prophesize(RestoreTrashItemHandlerInterface::class);

        $this->restoreTrashItemHandlerLocator->has('tags')->willReturn(true);
        $this->restoreTrashItemHandlerLocator->get('tags')->willReturn($restoreTrashItemHandler->reveal());

        $trashItem = new TrashItem();
        $trashItem->setResourceKey('tags');
        $trashItem->setResourceId('1');
        $trashItem->setResourceTitle('Tag Title');
        $restoreFormData = ['foo' => 'bar'];
        $object = new \stdClass();

        $restoreTrashItemHandler->restore($trashItem, $restoreFormData)->shouldBeCalled()->willReturn($object);
        $this->trashItemRepository->remove($trashItem)->shouldBeCalled();

        $result = $this->trashManager->restore($trashItem, $restoreFormData);

        static::assertSame($object, $result);
    }

    public function testRestoreTrashItemHandlerNotFound(): void
    {
        static::expectException(RestoreTrashItemHandlerNotFoundException::class);

        $this->restoreTrashItemHandlerLocator->has('tags')->willReturn(false);

        $trashItem = new TrashItem();
        $trashItem->setResourceKey('tags');

        $this->trashManager->restore($trashItem, []);
    }

    public function testRemoveWithoutRemoveTrashItemHandler(): void
    {
        $trashItem = new TrashItem();
        $trashItem->setResourceKey('tags');
        $trashItem->setResourceId('1');
        $trashItem->setResourceTitle('Tag Title');

        $this->removeTrashItemHandlerLocator->has('tags')->willReturn(false);

        $this->domainEventCollector->collect(Argument::type(TrashItemRemovedEvent::class))->shouldBeCalled();
        $this->trashItemRepository->remove($trashItem)->shouldBeCalled();

        $this->trashManager->remove($trashItem);
    }

    public function testRemoveWithRemoveTrashItemHandler(): void
    {
        $removeTrashItemHandler = $this->prophesize(RemoveTrashItemHandlerInterface::class);

        $trashItem = new TrashItem();
        $trashItem->setResourceKey('tags');
        $trashItem->setResourceId('1');
        $trashItem->setResourceTitle('Tag Title');

        $this->removeTrashItemHandlerLocator->has('tags')->willReturn(true);
        $this->removeTrashItemHandlerLocator->get('tags')->willReturn($removeTrashItemHandler->reveal());

        $removeTrashItemHandler->remove($trashItem)->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::type(TrashItemRemovedEvent::class))->shouldBeCalled();
        $this->trashItemRepository->remove($trashItem)->shouldBeCalled();

        $this->trashManager->remove($trashItem);
    }
}
