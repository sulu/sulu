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
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Event\TrashItemCreatedEvent;
use Sulu\Bundle\TrashBundle\Domain\Event\TrashItemRemovedEvent;
use Sulu\Bundle\TrashBundle\Domain\Event\TrashItemRestoredEvent;
use Sulu\Bundle\TrashBundle\Domain\Exception\RestoreTrashItemHandlerNotFoundException;
use Sulu\Bundle\TrashBundle\Domain\Exception\StoreTrashItemHandlerNotFoundException;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;

class TrashManager implements TrashManagerInterface
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
     * @var iterable<StoreTrashItemHandlerInterface>
     */
    private $storeTrashItemHandlers;

    /**
     * @var iterable<RestoreTrashItemHandlerInterface>
     */
    private $restoreTrashItemHandlers;

    /**
     * @param iterable<StoreTrashItemHandlerInterface> $storeTrashItemHandlers
     * @param iterable<RestoreTrashItemHandlerInterface> $restoreTrashItemHandlers
     */
    public function __construct(
        TrashItemRepositoryInterface $trashItemRepository,
        DomainEventCollectorInterface $domainEventCollector,
        iterable $storeTrashItemHandlers,
        iterable $restoreTrashItemHandlers
    ) {
        $this->trashItemRepository = $trashItemRepository;
        $this->domainEventCollector = $domainEventCollector;
        $this->storeTrashItemHandlers = $storeTrashItemHandlers;
        $this->restoreTrashItemHandlers = $restoreTrashItemHandlers;
    }

    public function store(string $resourceKey, object $object): TrashItemInterface
    {
        foreach ($this->storeTrashItemHandlers as $storeTrashItemHandler) {
            if (!$storeTrashItemHandler->supports($resourceKey)) {
                continue;
            }

            $trashItem = $storeTrashItemHandler->store($object);

            $this->domainEventCollector->collect(
                new TrashItemCreatedEvent($trashItem)
            );

            $this->trashItemRepository->addAndCommit($trashItem);

            return $trashItem;
        }

        throw new StoreTrashItemHandlerNotFoundException($resourceKey);
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData): object
    {
        $resourceKey = $trashItem->getResourceKey();

        foreach ($this->restoreTrashItemHandlers as $restoreTrashItemHandler) {
            if (!$restoreTrashItemHandler->supports($resourceKey)) {
                continue;
            }

            $object = $restoreTrashItemHandler->restore($trashItem, $restoreFormData);

            $translation = $trashItem->getTranslation();

            $this->domainEventCollector->collect(
                new TrashItemRestoredEvent(
                    (int) $trashItem->getId(),
                    $trashItem->getResourceKey(),
                    $trashItem->getResourceId(),
                    $translation->getTitle(),
                    $translation->getLocale()
                )
            );

            $this->trashItemRepository->removeAndCommit($trashItem);

            return $object;
        }

        throw new RestoreTrashItemHandlerNotFoundException($resourceKey);
    }

    public function remove(TrashItemInterface $trashItem): void
    {
        $translation = $trashItem->getTranslation();

        $this->domainEventCollector->collect(
            new TrashItemRemovedEvent(
                (int) $trashItem->getId(),
                $trashItem->getResourceKey(),
                $trashItem->getResourceId(),
                $translation->getTitle(),
                $translation->getLocale()
            )
        );

        $this->trashItemRepository->removeAndCommit($trashItem);
    }
}
