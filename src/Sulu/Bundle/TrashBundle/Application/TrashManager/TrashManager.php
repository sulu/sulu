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

namespace Sulu\Bundle\TrashBundle\Application\TrashManager;

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RemoveTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Event\TrashItemRemovedEvent;
use Sulu\Bundle\TrashBundle\Domain\Exception\RestoreTrashItemHandlerNotFoundException;
use Sulu\Bundle\TrashBundle\Domain\Exception\StoreTrashItemHandlerNotFoundException;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class TrashManager implements TrashManagerInterface
{
    /**
     * @var TrashItemRepositoryInterface
     */
    private $trashItemRepository;

    /**
     * @var DomainEventCollectorInterface
     */
    private $domainEventCollector;

    /**
     * @var ServiceLocator
     */
    private $storeTrashItemHandlerLocator;

    /**
     * @var ServiceLocator
     */
    private $restoreTrashItemHandlerLocator;

    /**
     * @var ServiceLocator
     */
    private $removeTrashItemHandlerLocator;

    public function __construct(
        TrashItemRepositoryInterface $trashItemRepository,
        DomainEventCollectorInterface $domainEventCollector,
        ServiceLocator $storeTrashItemHandlerLocator,
        ServiceLocator $restoreTrashItemHandlerLocator,
        ServiceLocator $removeTrashItemHandlerLocator
    ) {
        $this->trashItemRepository = $trashItemRepository;
        $this->domainEventCollector = $domainEventCollector;
        $this->storeTrashItemHandlerLocator = $storeTrashItemHandlerLocator;
        $this->restoreTrashItemHandlerLocator = $restoreTrashItemHandlerLocator;
        $this->removeTrashItemHandlerLocator = $removeTrashItemHandlerLocator;
    }

    public function store(string $resourceKey, object $object, array $options = []): TrashItemInterface
    {
        if (!$this->storeTrashItemHandlerLocator->has($resourceKey)) {
            throw new StoreTrashItemHandlerNotFoundException($resourceKey);
        }

        /** @var StoreTrashItemHandlerInterface $storeTrashItemHandler */
        $storeTrashItemHandler = $this->storeTrashItemHandlerLocator->get($resourceKey);

        $trashItem = $storeTrashItemHandler->store($object, $options);

        $this->trashItemRepository->add($trashItem);

        return $trashItem;
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $resourceKey = $trashItem->getResourceKey();

        if (!$this->restoreTrashItemHandlerLocator->has($resourceKey)) {
            throw new RestoreTrashItemHandlerNotFoundException($resourceKey);
        }

        /** @var RestoreTrashItemHandlerInterface $restoreTrashItemHandler */
        $restoreTrashItemHandler = $this->restoreTrashItemHandlerLocator->get($resourceKey);

        $object = $restoreTrashItemHandler->restore($trashItem, $restoreFormData);

        $this->trashItemRepository->remove($trashItem);

        return $object;
    }

    public function remove(TrashItemInterface $trashItem): void
    {
        $resourceKey = $trashItem->getResourceKey();

        if ($this->removeTrashItemHandlerLocator->has($resourceKey)) {
            /** @var RemoveTrashItemHandlerInterface $removeTrashItemHandler */
            $removeTrashItemHandler = $this->removeTrashItemHandlerLocator->get($resourceKey);

            $removeTrashItemHandler->remove($trashItem);
        }

        $translation = $trashItem->getTranslation(null, true);

        $this->domainEventCollector->collect(
            new TrashItemRemovedEvent(
                (int) $trashItem->getId(),
                $trashItem->getResourceKey(),
                $trashItem->getResourceId(),
                $translation->getTitle(),
                $translation->getLocale()
            )
        );

        $this->trashItemRepository->remove($trashItem);
    }
}
